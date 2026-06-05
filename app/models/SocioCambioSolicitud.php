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
        $this->db->query("SELECT s.*, u.rut, u.nombre, u.apellido_paterno, u.apellido_materno, u.email AS email_actual,
                u.telefono AS telefono_actual, u.genero AS genero_actual, u.fecha_nacimiento AS fecha_nacimiento_actual,
                u.estado_civil AS estado_civil_actual, u.nacionalidad AS nacionalidad_actual, u.profesion AS profesion_actual,
                u.calle_id AS usuario_calle_id, u.numero_casa AS usuario_numero_casa,
                u.latitud AS usuario_latitud, u.longitud AS usuario_longitud, u.link_google AS usuario_link_google,
                m.calle_id AS mem_calle_id, m.numero_casa AS mem_numero_casa, m.direccion_texto AS mem_direccion_texto,
                m.latitud AS mem_latitud, m.longitud AS mem_longitud, m.link_google AS mem_link_google,
                c.nombre AS calle_nombre_actual
            FROM socio_cambio_solicitudes s
            INNER JOIN usuarios u ON u.id = s.usuario_id
            LEFT JOIN usuario_membresias m ON m.usuario_id = s.usuario_id AND m.junta_id = s.junta_id AND m.estado = 1
            LEFT JOIN calles c ON c.id = COALESCE(m.calle_id, u.calle_id)
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
            $this->lastError = 'Falta la tabla socio_cambio_solicitudes.';
            return false;
        }
        try {
            $this->db->query("UPDATE socio_cambio_solicitudes
                SET status = 'approved', reviewed_by = :reviewer, reviewed_at = NOW()
                WHERE id = :id AND junta_id = :junta_id AND status = 'pending'");
            $this->db->bind(':reviewer', $reviewerId);
            $this->db->bind(':id', $id);
            $this->db->bind(':junta_id', $juntaId);
            if (!$this->db->execute()) {
                $this->lastError = 'No se pudo aprobar la solicitud.';
                return false;
            }
            if ($this->db->rowCount() < 1) {
                $this->lastError = 'Solicitud no encontrada o ya fue procesada.';
                return false;
            }
            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function reject(int $id, int $juntaId, int $reviewerId, string $motivo = ''): bool {
        if (!$this->hasTable()) {
            $this->lastError = 'Falta la tabla socio_cambio_solicitudes.';
            return false;
        }
        try {
            $this->db->query("UPDATE socio_cambio_solicitudes
                SET status = 'rejected', motivo_rechazo = :motivo, reviewed_by = :reviewer, reviewed_at = NOW()
                WHERE id = :id AND junta_id = :junta_id AND status = 'pending'");
            $this->db->bind(':motivo', $motivo !== '' ? $motivo : null);
            $this->db->bind(':reviewer', $reviewerId);
            $this->db->bind(':id', $id);
            $this->db->bind(':junta_id', $juntaId);
            if (!$this->db->execute()) {
                $this->lastError = 'No se pudo rechazar la solicitud.';
                return false;
            }
            if ($this->db->rowCount() < 1) {
                $this->lastError = 'Solicitud no encontrada o ya fue procesada.';
                return false;
            }
            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public static function decodeDatos($row): array {
        if (!$row || empty($row->datos_json)) {
            return [];
        }
        $decoded = json_decode($row->datos_json, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function resolveActualDomicilio($row): array {
        $calleId = $row->mem_calle_id ?? $row->usuario_calle_id ?? null;
        $numero = $row->mem_numero_casa ?? $row->usuario_numero_casa ?? '';
        $direccion = $row->mem_direccion_texto ?? '';
        $lat = $row->mem_latitud ?? $row->usuario_latitud ?? '';
        $lng = $row->mem_longitud ?? $row->usuario_longitud ?? '';
        $link = $row->mem_link_google ?? $row->usuario_link_google ?? '';

        return [
            'calle_id' => $calleId !== null ? (int)$calleId : 0,
            'numero_casa' => (string)$numero,
            'direccion_texto' => (string)$direccion,
            'calle_nombre' => (string)($row->calle_nombre_actual ?? ''),
            'latitud' => $lat !== null ? (string)$lat : '',
            'longitud' => $lng !== null ? (string)$lng : '',
            'link_google' => (string)$link,
        ];
    }

    public static function buildFieldComparisons($row, array $datos, array $callesMap, bool $usesCalles): array {
        require_once APPROOT . '/core/SocioInput.php';

        $fmtDate = static function ($value): string {
            if (empty($value)) {
                return '—';
            }
            $ts = strtotime((string)$value);
            return ($ts !== false) ? date('d-m-Y', $ts) : '—';
        };
        $fmtTel = static function ($value): string {
            $display = SocioInput::formatTelefonoDisplay($value ?? '');
            return $display !== '' ? $display : '—';
        };
        $norm = static function ($value): string {
            return trim((string)($value ?? ''));
        };

        $actualDom = self::resolveActualDomicilio($row);
        if ($usesCalles) {
            $actualDomicilioText = ($actualDom['calle_nombre'] !== '' ? $actualDom['calle_nombre'] : '—')
                . ($actualDom['numero_casa'] !== '' ? ' #' . $actualDom['numero_casa'] : '');
            $nuevoCalleId = (int)($datos['calle_id'] ?? 0);
            $nuevoCalleNom = $callesMap[$nuevoCalleId] ?? ($nuevoCalleId > 0 ? 'Calle #' . $nuevoCalleId : '—');
            $nuevoDomicilioText = $nuevoCalleNom
                . (!empty($datos['numero_casa']) ? ' #' . $datos['numero_casa'] : '');
        } else {
            $actualDomicilioText = $actualDom['direccion_texto'] !== '' ? $actualDom['direccion_texto'] : '—';
            $nuevoDomicilioText = $norm($datos['direccion_texto'] ?? '') ?: '—';
        }

        $actualCoords = ($actualDom['latitud'] !== '' && $actualDom['longitud'] !== '')
            ? $actualDom['latitud'] . ', ' . $actualDom['longitud']
            : '—';
        $nuevoCoords = (!empty($datos['latitud']) && !empty($datos['longitud']))
            ? $datos['latitud'] . ', ' . $datos['longitud']
            : '—';

        $rows = [
            ['key' => 'email', 'label' => 'Correo electrónico', 'actual' => $norm($row->email_actual ?? '') ?: '—', 'nuevo' => $norm($datos['email'] ?? '') ?: '—'],
            ['key' => 'telefono', 'label' => 'Teléfono', 'actual' => $fmtTel($row->telefono_actual ?? ''), 'nuevo' => $fmtTel($datos['telefono'] ?? '')],
            ['key' => 'genero', 'label' => 'Género', 'actual' => SocioInput::generoLabel($row->genero_actual ?? '') ?: '—', 'nuevo' => SocioInput::generoLabel($datos['genero'] ?? '') ?: '—'],
            ['key' => 'fecha_nacimiento', 'label' => 'Fecha de nacimiento', 'actual' => $fmtDate($row->fecha_nacimiento_actual ?? ''), 'nuevo' => $fmtDate($datos['fecha_nacimiento'] ?? '')],
            ['key' => 'estado_civil', 'label' => 'Estado civil', 'actual' => SocioInput::estadoCivilLabel($row->estado_civil_actual ?? '') ?: '—', 'nuevo' => SocioInput::estadoCivilLabel($datos['estado_civil'] ?? '') ?: '—'],
            ['key' => 'nacionalidad', 'label' => 'Nacionalidad', 'actual' => $norm($row->nacionalidad_actual ?? '') ?: '—', 'nuevo' => $norm($datos['nacionalidad'] ?? '') ?: '—'],
            ['key' => 'profesion', 'label' => 'Profesión u oficio', 'actual' => $norm($row->profesion_actual ?? '') ?: '—', 'nuevo' => $norm($datos['profesion'] ?? '') ?: '—'],
            ['key' => 'domicilio', 'label' => $usesCalles ? 'Domicilio (calle y número)' : 'Dirección', 'actual' => $actualDomicilioText ?: '—', 'nuevo' => $nuevoDomicilioText ?: '—'],
            ['key' => 'coordenadas', 'label' => 'Coordenadas (pin mapa)', 'actual' => $actualCoords, 'nuevo' => $nuevoCoords],
        ];

        $actualLink = $actualDom['link_google'] ?? '';
        $nuevoLink = $norm($datos['link_google'] ?? '');
        if ($actualLink !== '' || $nuevoLink !== '') {
            $rows[] = [
                'key' => 'link_google',
                'label' => 'Enlace mapa',
                'actual' => $actualLink !== '' ? $actualLink : '—',
                'nuevo' => $nuevoLink !== '' ? $nuevoLink : '—',
            ];
        }

        foreach ($rows as &$item) {
            $item['changed'] = ($item['actual'] !== $item['nuevo']);
        }
        unset($item);

        return $rows;
    }
}
