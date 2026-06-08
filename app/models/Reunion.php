<?php
class Reunion extends Model {

    private static $hasConvocatoriaColumns = null;

    public function hasConvocatoriaColumns(): bool {
        if (self::$hasConvocatoriaColumns === null) {
            try {
                $this->db->query('SELECT temas_tratar FROM reuniones LIMIT 1');
                $this->db->execute();
                self::$hasConvocatoriaColumns = true;
            } catch (Exception $e) {
                self::$hasConvocatoriaColumns = false;
            }
        }
        return self::$hasConvocatoriaColumns;
    }

    public function createReunion(array $data): ?int {
        if ($this->hasConvocatoriaColumns()) {
            $this->db->query('INSERT INTO reuniones
                (junta_id, titulo, descripcion, temas_tratar, fecha_reunion, estado, convocada_por, email_convocatoria)
                VALUES (:junta_id, :titulo, :descripcion, :temas_tratar, :fecha_reunion, :estado, :convocada_por, :email_convocatoria)');
            $this->db->bind(':temas_tratar', $data['temas_tratar'] ?? null);
            $this->db->bind(':convocada_por', $data['convocada_por'] ?? null);
            $this->db->bind(':email_convocatoria', !empty($data['email_convocatoria']) ? 1 : 0);
        } else {
            $this->db->query('INSERT INTO reuniones (junta_id, titulo, descripcion, fecha_reunion, estado)
                VALUES (:junta_id, :titulo, :descripcion, :fecha_reunion, :estado)');
        }
        $this->db->bind(':junta_id', $data['junta_id']);
        $this->db->bind(':titulo', $data['titulo']);
        $this->db->bind(':descripcion', $data['descripcion'] ?? null);
        $this->db->bind(':fecha_reunion', $data['fecha_reunion']);
        $this->db->bind(':estado', $data['estado'] ?? 'programada');
        if (!$this->db->execute()) {
            return null;
        }
        return (int)$this->db->lastInsertId();
    }

    public function getReunionesByJunta(int $juntaId): array {
        $convocadosSql = '(SELECT COUNT(*) FROM reunion_convocados WHERE reunion_id = r.id) AS total_convocados';
        try {
            $this->db->query('SELECT 1 FROM reunion_convocados LIMIT 1');
            $this->db->execute();
        } catch (Exception $e) {
            $convocadosSql = '0 AS total_convocados';
        }

        $this->db->query("SELECT r.*, {$convocadosSql},
            (SELECT COUNT(*) FROM asistencia WHERE reunion_id = r.id AND asistio = 1) AS presentes,
            (SELECT COUNT(*) FROM asistencia WHERE reunion_id = r.id) AS total_registrados
            FROM reuniones r
            WHERE r.junta_id = :junta_id
            ORDER BY
                CASE WHEN r.fecha_reunion >= NOW() THEN 0 ELSE 1 END ASC,
                CASE WHEN r.fecha_reunion >= NOW() THEN r.fecha_reunion END ASC,
                r.fecha_reunion DESC");
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    public function getReunionById(int $id) {
        $this->db->query('SELECT * FROM reuniones WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function getReunionByIdAndJunta(int $id, int $juntaId) {
        $this->db->query('SELECT * FROM reuniones WHERE id = :id AND junta_id = :junta_id LIMIT 1');
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->single();
    }

    public function getTemasText(object $reunion): string {
        if ($this->hasConvocatoriaColumns() && !empty($reunion->temas_tratar)) {
            return (string)$reunion->temas_tratar;
        }
        return (string)($reunion->descripcion ?? '');
    }

    public function updateConvocatoria(int $id, int $juntaId, array $data): bool {
        $reunion = $this->getReunionByIdAndJunta($id, $juntaId);
        if (!$reunion || $reunion->estado === 'cancelada') {
            return false;
        }
        if ($this->hasConvocatoriaColumns()) {
            $this->db->query('UPDATE reuniones SET titulo = :titulo, temas_tratar = :temas_tratar,
                fecha_reunion = :fecha_reunion WHERE id = :id AND junta_id = :junta_id');
            $this->db->bind(':temas_tratar', $data['temas_tratar'] ?? null);
        } else {
            $this->db->query('UPDATE reuniones SET titulo = :titulo, descripcion = :descripcion,
                fecha_reunion = :fecha_reunion WHERE id = :id AND junta_id = :junta_id');
            $this->db->bind(':descripcion', $data['temas_tratar'] ?? null);
        }
        $this->db->bind(':titulo', $data['titulo']);
        $this->db->bind(':fecha_reunion', $data['fecha_reunion']);
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->execute();
    }

    public function updateResultados(int $id, int $juntaId, string $resultados, ?string $horaInicioReal, bool $finalizar): bool {
        if (!$this->hasConvocatoriaColumns()) {
            return false;
        }
        $sql = 'UPDATE reuniones SET resultados = :resultados';
        if ($horaInicioReal !== null) {
            $sql .= ', hora_inicio_real = :hora_inicio_real';
        }
        if ($finalizar) {
            $sql .= ", estado = 'realizada'";
        }
        $sql .= ' WHERE id = :id AND junta_id = :junta_id';
        $this->db->query($sql);
        $this->db->bind(':resultados', $resultados !== '' ? $resultados : null);
        if ($horaInicioReal !== null) {
            $this->db->bind(':hora_inicio_real', $horaInicioReal);
        }
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->execute();
    }

    public function updateEstado(int $id, string $estado): bool {
        $this->db->query('UPDATE reuniones SET estado = :estado WHERE id = :id');
        $this->db->bind(':estado', $estado);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    public function delete(int $id): bool {
        $this->db->query('DELETE FROM reuniones WHERE id = :id');
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Eventos del mes para calendario: día => [{id, titulo, hora, estado}]
     * @param int|null $usuarioId Si se indica, solo reuniones donde el usuario está convocado.
     */
    public function getEventosCalendarioMes(int $juntaId, int $mes, int $anio, ?int $usuarioId = null): array {
        $desde = sprintf('%04d-%02d-01 00:00:00', $anio, $mes);
        $hasta = date('Y-m-t 23:59:59', strtotime($desde));
        $eventos = [];

        if ($usuarioId !== null) {
            try {
                $this->db->query('SELECT r.id, r.titulo, r.fecha_reunion, r.estado
                    FROM reuniones r
                    INNER JOIN reunion_convocados rc ON rc.reunion_id = r.id AND rc.usuario_id = :usuario_id
                    WHERE r.junta_id = :junta_id AND r.fecha_reunion >= :desde AND r.fecha_reunion <= :hasta
                    ORDER BY r.fecha_reunion ASC');
                $this->db->bind(':usuario_id', $usuarioId);
            } catch (Exception $e) {
                return [];
            }
        } else {
            $this->db->query('SELECT id, titulo, fecha_reunion, estado FROM reuniones
                WHERE junta_id = :junta_id AND fecha_reunion >= :desde AND fecha_reunion <= :hasta
                ORDER BY fecha_reunion ASC');
        }

        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);

        foreach ($this->db->resultSet() as $row) {
            $dia = (int)date('j', strtotime($row->fecha_reunion));
            if (!isset($eventos[$dia])) {
                $eventos[$dia] = [];
            }
            $eventos[$dia][] = [
                'id' => (int)$row->id,
                'titulo' => (string)$row->titulo,
                'hora' => date('H:i', strtotime($row->fecha_reunion)),
                'estado' => (string)$row->estado,
            ];
        }
        return $eventos;
    }
}
