<?php
class ReunionConvocado extends Model {

    public function replaceForReunion(int $reunionId, array $usuarioIds): void {
        $this->db->query('DELETE FROM reunion_convocados WHERE reunion_id = :reunion_id');
        $this->db->bind(':reunion_id', $reunionId);
        $this->db->execute();

        foreach (array_unique(array_map('intval', $usuarioIds)) as $uid) {
            if ($uid <= 0) {
                continue;
            }
            $this->db->query('INSERT INTO reunion_convocados (reunion_id, usuario_id, notificado_email)
                VALUES (:reunion_id, :usuario_id, 0)');
            $this->db->bind(':reunion_id', $reunionId);
            $this->db->bind(':usuario_id', $uid);
            $this->db->execute();
        }
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
        $this->db->query('SELECT u.id, u.nombre, u.email, u.rut, rc.notificado_email
            FROM reunion_convocados rc
            INNER JOIN usuarios u ON u.id = rc.usuario_id
            INNER JOIN usuario_membresias m ON m.usuario_id = u.id AND m.junta_id = :junta_id AND m.estado = 1
            WHERE rc.reunion_id = :reunion_id
            ORDER BY u.nombre ASC');
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
        $this->db->query("SELECT r.*,
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
