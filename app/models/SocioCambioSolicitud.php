<?php

class SocioCambioSolicitud extends Model {

    private $lastError = '';

    public function getLastError(): string {
        return $this->lastError;
    }

    public function hasTable(): bool {
        try {
            $this->db->query("SELECT 1 FROM information_schema.tables
                WHERE table_schema = DATABASE() AND table_name = 'socio_cambio_solicitudes' LIMIT 1");
            $row = $this->db->single();
            return !empty($row);
        } catch (Exception $e) {
            return false;
        }
    }

    public function getPendingByJunta(int $juntaId): array {
        if (!$this->hasTable()) {
            return [];
        }
        $this->db->query("SELECT s.*, u.rut, u.nombre, u.apellido_paterno, u.apellido_materno, u.email AS email_actual
            FROM socio_cambio_solicitudes s
            INNER JOIN usuarios u ON u.id = s.usuario_id
            WHERE s.junta_id = :junta_id AND s.status = 'pending'
            ORDER BY s.created_at ASC");
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    public function getPendingById(int $id, int $juntaId) {
        if (!$this->hasTable()) {
            return null;
        }
        $this->db->query("SELECT s.*, u.rut, u.nombre, u.apellido_paterno, u.apellido_materno, u.email AS email_actual,
                u.telefono AS telefono_actual, u.genero AS genero_actual, u.fecha_nacimiento AS fecha_nacimiento_actual,
                u.estado_civil AS estado_civil_actual, u.nacionalidad AS nacionalidad_actual, u.profesion AS profesion_actual
            FROM socio_cambio_solicitudes s
            INNER JOIN usuarios u ON u.id = s.usuario_id
            WHERE s.id = :id AND s.junta_id = :junta_id AND s.status = 'pending'");
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->single();
    }

    public function getPendingForUsuarioJunta(int $usuarioId, int $juntaId) {
        if (!$this->hasTable()) {
            return null;
        }
        $this->db->query("SELECT * FROM socio_cambio_solicitudes
            WHERE usuario_id = :usuario_id AND junta_id = :junta_id AND status = 'pending' LIMIT 1");
        $this->db->bind(':usuario_id', $usuarioId);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->single();
    }

    public function create(int $usuarioId, int $juntaId, ?int $membresiaId, array $datos): ?int {
        $this->lastError = '';
        if (!$this->hasTable()) {
            $this->lastError = 'Falta la tabla socio_cambio_solicitudes. Ejecute sql/create_socio_cambio_solicitudes.sql en su base de datos.';
            return null;
        }
        try {
            $payload = json_encode($datos, JSON_UNESCAPED_UNICODE);
            if ($payload === false) {
                $this->lastError = 'No se pudieron serializar los datos del formulario.';
                return null;
            }

            $existing = $this->getPendingForUsuarioJunta($usuarioId, $juntaId);
            if ($existing) {
                $this->db->query("UPDATE socio_cambio_solicitudes
                    SET datos_json = :datos, membresia_id = :membresia_id, created_at = NOW()
                    WHERE id = :id");
                $this->db->bind(':datos', $payload);
                $this->db->bind(':membresia_id', $membresiaId);
                $this->db->bind(':id', $existing->id);
                if ($this->db->execute()) {
                    return (int)$existing->id;
                }
                $this->lastError = 'No se pudo actualizar la solicitud pendiente.';
                return null;
            }

            $this->db->query("INSERT INTO socio_cambio_solicitudes (usuario_id, junta_id, membresia_id, datos_json, status)
                VALUES (:usuario_id, :junta_id, :membresia_id, :datos, 'pending')");
            $this->db->bind(':usuario_id', $usuarioId);
            $this->db->bind(':junta_id', $juntaId);
            $this->db->bind(':membresia_id', $membresiaId);
            $this->db->bind(':datos', $payload);
            if ($this->db->execute()) {
                $id = (int)$this->db->lastInsertId();
                if ($id > 0) {
                    return $id;
                }
                $this->lastError = 'La solicitud se guardó pero no se obtuvo el identificador.';
                return null;
            }
            $this->lastError = 'No se pudo insertar la solicitud.';
            return null;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }

    public function approve(int $id, int $juntaId, int $reviewerId): bool {
        if (!$this->hasTable()) {
            return false;
        }
        $this->db->query("UPDATE socio_cambio_solicitudes
            SET status = 'approved', reviewed_by = :reviewer, reviewed_at = NOW()
            WHERE id = :id AND junta_id = :junta_id AND status = 'pending'");
        $this->db->bind(':reviewer', $reviewerId);
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->execute();
    }

    public function reject(int $id, int $juntaId, int $reviewerId, string $motivo = ''): bool {
        if (!$this->hasTable()) {
            return false;
        }
        $this->db->query("UPDATE socio_cambio_solicitudes
            SET status = 'rejected', motivo_rechazo = :motivo, reviewed_by = :reviewer, reviewed_at = NOW()
            WHERE id = :id AND junta_id = :junta_id AND status = 'pending'");
        $this->db->bind(':motivo', $motivo !== '' ? $motivo : null);
        $this->db->bind(':reviewer', $reviewerId);
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->execute();
    }

    public static function decodeDatos($row): array {
        if (!$row || empty($row->datos_json)) {
            return [];
        }
        $decoded = json_decode($row->datos_json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
