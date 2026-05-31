<?php
class Invitation extends Model {

    public function create($juntaId, $hoursValid = 24) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . (int)$hoursValid . ' hours'));
        $this->db->query("INSERT INTO invitations (junta_id, token, expires_at, status) VALUES (:junta_id, :token, :expires_at, 'pending')");
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':token', $token);
        $this->db->bind(':expires_at', $expiresAt);
        if ($this->db->execute()) {
            return [
                'id' => $this->db->lastInsertId(),
                'token' => $token,
                'expires_at' => $expiresAt,
            ];
        }
        return false;
    }

    public function getValidByToken($token) {
        $this->db->query("SELECT i.*, j.nombre AS junta_nombre, j.comuna
            FROM invitations i
            INNER JOIN juntas_vecinos j ON j.id = i.junta_id
            WHERE i.token = :token AND i.status = 'pending' AND i.expires_at > NOW()
            LIMIT 1");
        $this->db->bind(':token', $token);
        return $this->db->single();
    }

    public function getActiveByJunta($juntaId) {
        $this->db->query("SELECT * FROM invitations
            WHERE junta_id = :junta_id AND status = 'pending' AND expires_at > NOW()
            ORDER BY created_at DESC");
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    public function revoke($id, $juntaId) {
        $this->db->query("UPDATE invitations SET status = 'revoked' WHERE id = :id AND junta_id = :junta_id");
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->execute();
    }
}
