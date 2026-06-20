<?php
class ReunionConvocado extends Model {

    public function replaceForReunion(int $reunionId, array $usuarioIds): void {
        $this->db->query('DELETE FROM reunion_convocados WHERE reunion_id = :reunion_id');
        $this->db->bind(':reunion_id', $reunionId);
        $this->db->execute();

        $hasRsvp = $this->hasRsvpColumns();
        foreach (array_unique(array_map('intval', $usuarioIds)) as $uid) {
            if ($uid <= 0) {
                continue;
            }
            if ($hasRsvp) {
                $token = bin2hex(random_bytes(24));
                $this->db->query('INSERT INTO reunion_convocados (reunion_id, usuario_id, notificado_email, rsvp_estado, rsvp_token)
                    VALUES (:reunion_id, :usuario_id, 0, \'pendiente\', :rsvp_token)');
                $this->db->bind(':rsvp_token', $token);
            } else {
                $this->db->query('INSERT INTO reunion_convocados (reunion_id, usuario_id, notificado_email)
                    VALUES (:reunion_id, :usuario_id, 0)');
            }
            $this->db->bind(':reunion_id', $reunionId);
            $this->db->bind(':usuario_id', $uid);
            $this->db->execute();
        }
    }

    private static $hasRsvpColumns = null;

    public function hasRsvpColumns(): bool {
        if (self::$hasRsvpColumns === null) {
            try {
                $this->db->query('SELECT rsvp_estado FROM reunion_convocados LIMIT 1');
                $this->db->execute();
                self::$hasRsvpColumns = true;
            } catch (Exception $e) {
                self::$hasRsvpColumns = false;
            }
        }
        return self::$hasRsvpColumns;
    }

    public function updateRsvp(int $reunionId, int $usuarioId, string $estado): bool {
        if (!$this->hasRsvpColumns() || !in_array($estado, ['confirmado', 'rechazado', 'pendiente'], true)) {
            return false;
        }
        $this->db->query('UPDATE reunion_convocados SET rsvp_estado = :estado, rsvp_at = NOW()
            WHERE reunion_id = :reunion_id AND usuario_id = :usuario_id');
        $this->db->bind(':estado', $estado);
        $this->db->bind(':reunion_id', $reunionId);
        $this->db->bind(':usuario_id', $usuarioId);
        return $this->db->execute();
    }

    public function updateRsvpByToken(string $token, string $estado): ?object {
        if (!$this->hasRsvpColumns()) {
            return null;
        }
        $this->db->query('SELECT rc.*, r.junta_id, r.titulo, r.fecha_reunion, r.estado AS reunion_estado
            FROM reunion_convocados rc
            INNER JOIN reuniones r ON r.id = rc.reunion_id
            WHERE rc.rsvp_token = :token LIMIT 1');
        $this->db->bind(':token', $token);
        $row = $this->db->single();
        if (!$row || $row->reunion_estado !== 'programada') {
            return null;
        }
        if ($this->updateRsvp((int)$row->reunion_id, (int)$row->usuario_id, $estado)) {
            $row->rsvp_estado = $estado;
        }
        return $row;
    }

    public function getRsvpStats(int $reunionId): array {
        if (!$this->hasRsvpColumns()) {
            return ['total' => 0, 'confirmados' => 0, 'rechazados' => 0, 'pendientes' => 0];
        }
        $this->db->query("SELECT
            COUNT(*) AS total,
            SUM(CASE WHEN rsvp_estado = 'confirmado' THEN 1 ELSE 0 END) AS confirmados,
            SUM(CASE WHEN rsvp_estado = 'rechazado' THEN 1 ELSE 0 END) AS rechazados,
            SUM(CASE WHEN rsvp_estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes
            FROM reunion_convocados WHERE reunion_id = :reunion_id");
        $this->db->bind(':reunion_id', $reunionId);
        $r = $this->db->single();
        return [
            'total' => (int)($r->total ?? 0),
            'confirmados' => (int)($r->confirmados ?? 0),
            'rechazados' => (int)($r->rechazados ?? 0),
            'pendientes' => (int)($r->pendientes ?? 0),
        ];
    }

    public function getConvocadosSinRespuesta(int $reunionId, int $juntaId): array {
        if (!$this->hasRsvpColumns()) {
            return [];
        }
        $this->db->query("SELECT u.id, u.nombre, u.email, rc.rsvp_token
            FROM reunion_convocados rc
            INNER JOIN usuarios u ON u.id = rc.usuario_id
            INNER JOIN usuario_membresias m ON m.usuario_id = u.id AND m.junta_id = :junta_id AND m.estado = 1
            WHERE rc.reunion_id = :reunion_id AND rc.rsvp_estado = 'pendiente'
            ORDER BY u.nombre ASC");
        $this->db->bind(':reunion_id', $reunionId);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    public function getRsvpForUsuario(int $reunionId, int $usuarioId): ?string {
        if (!$this->hasRsvpColumns()) {
            return null;
        }
        $this->db->query('SELECT rsvp_estado FROM reunion_convocados
            WHERE reunion_id = :reunion_id AND usuario_id = :usuario_id LIMIT 1');
        $this->db->bind(':reunion_id', $reunionId);
        $this->db->bind(':usuario_id', $usuarioId);
        $r = $this->db->single();
        return $r ? (string)$r->rsvp_estado : null;
    }

    public function getRsvpToken(int $reunionId, int $usuarioId): ?string {
        if (!$this->hasRsvpColumns()) {
            return null;
        }
        $this->db->query('SELECT rsvp_token FROM reunion_convocados
            WHERE reunion_id = :reunion_id AND usuario_id = :usuario_id LIMIT 1');
        $this->db->bind(':reunion_id', $reunionId);
        $this->db->bind(':usuario_id', $usuarioId);
        $r = $this->db->single();
        return ($r && !empty($r->rsvp_token)) ? (string)$r->rsvp_token : null;
    }

    public function markEmailSent(int $reunionId, int $usuarioId): void {
        $this->db->query('UPDATE reunion_convocados SET notificado_email = 1
            WHERE reunion_id = :reunion_id AND usuario_id = :usuario_id');
        $this->db->bind(':reunion_id', $reunionId);
        $this->db->bind(':usuario_id', $usuarioId);
        $this->db->execute();
    }

    public function getUsuarioIdsByReunion(int $reunionId): array {
        $this->db->query('SELECT usuario_id FROM reunion_convocados WHERE reunion_id = :reunion_id');
        $this->db->bind(':reunion_id', $reunionId);
        $rows = $this->db->resultSet();
        return array_map(static fn($r) => (int)$r->usuario_id, $rows);
    }

    public function getUsuariosByReunion(int $reunionId, int $juntaId): array {
        $rsvpCols = $this->hasRsvpColumns()
            ? ', rc.rsvp_estado, rc.rsvp_at'
            : '';
        $this->db->query("SELECT u.id, u.nombre, u.email, u.rut, rc.notificado_email{$rsvpCols}
            FROM reunion_convocados rc
            INNER JOIN usuarios u ON u.id = rc.usuario_id
            INNER JOIN usuario_membresias m ON m.usuario_id = u.id AND m.junta_id = :junta_id AND m.estado = 1
            WHERE rc.reunion_id = :reunion_id
            ORDER BY u.nombre ASC");
        $this->db->bind(':reunion_id', $reunionId);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    public function isConvocado(int $reunionId, int $usuarioId): bool {
        $this->db->query('SELECT id FROM reunion_convocados
            WHERE reunion_id = :reunion_id AND usuario_id = :usuario_id LIMIT 1');
        $this->db->bind(':reunion_id', $reunionId);
        $this->db->bind(':usuario_id', $usuarioId);
        return (bool)$this->db->single();
    }

    /** Reuniones donde el usuario fue convocado, orden: futuras (cercanas primero) → pasadas */
    public function getReunionesForUsuario(int $juntaId, int $usuarioId): array {
        $rsvpCol = $this->hasRsvpColumns() ? ', rc.rsvp_estado' : '';
        $this->db->query("SELECT r.*{$rsvpCol},
            (SELECT COUNT(*) FROM asistencia WHERE reunion_id = r.id AND asistio = 1) AS presentes,
            (SELECT COUNT(*) FROM asistencia WHERE reunion_id = r.id) AS total_registrados,
            (SELECT asistio FROM asistencia WHERE reunion_id = r.id AND socio_id = :usuario_asist LIMIT 1) AS yo_asisti
            FROM reuniones r
            INNER JOIN reunion_convocados rc ON rc.reunion_id = r.id AND rc.usuario_id = :usuario_id
            WHERE r.junta_id = :junta_id
            ORDER BY
                CASE WHEN r.fecha_reunion >= NOW() THEN 0 ELSE 1 END ASC,
                CASE WHEN r.fecha_reunion >= NOW() THEN r.fecha_reunion END ASC,
                r.fecha_reunion DESC");
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':usuario_id', $usuarioId);
        $this->db->bind(':usuario_asist', $usuarioId);
        return $this->db->resultSet();
    }

    public function getProximaForUsuario(int $juntaId, int $usuarioId) {
        $this->db->query("SELECT r.* FROM reuniones r
            INNER JOIN reunion_convocados rc ON rc.reunion_id = r.id AND rc.usuario_id = :usuario_id
            WHERE r.junta_id = :junta_id AND r.estado = 'programada' AND r.fecha_reunion >= NOW()
            ORDER BY r.fecha_reunion ASC LIMIT 1");
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':usuario_id', $usuarioId);
        return $this->db->single();
    }
}
