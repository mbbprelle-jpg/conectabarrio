<?php
class Reunion extends Model {
    
    // Crear una nueva reunión
    public function createReunion($data) {
        $this->db->query("INSERT INTO reuniones (junta_id, titulo, descripcion, fecha_reunion, estado) 
                         VALUES (:junta_id, :titulo, :descripcion, :fecha_reunion, :estado)");
        $this->db->bind(':junta_id', $data['junta_id']);
        $this->db->bind(':titulo', $data['titulo']);
        $this->db->bind(':descripcion', $data['descripcion'] ?? null);
        $this->db->bind(':fecha_reunion', $data['fecha_reunion']);
        $this->db->bind(':estado', $data['estado'] ?? 'programada');

        return $this->db->execute();
    }

    // Obtener todas las reuniones de una junta
    public function getReunionesByJunta($juntaId) {
        $this->db->query("SELECT r.*, 
                         (SELECT COUNT(*) FROM asistencia WHERE reunion_id = r.id AND asistio = 1) as presentes,
                         (SELECT COUNT(*) FROM asistencia WHERE reunion_id = r.id) as total_registrados
                         FROM reuniones r 
                         WHERE r.junta_id = :junta_id 
                         ORDER BY r.fecha_reunion DESC");
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    // Obtener una reunión específica por su ID
    public function getReunionById($id) {
        $this->db->query("SELECT * FROM reuniones WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Actualizar estado de la reunión
    public function updateEstado($id, $estado) {
        $this->db->query("UPDATE reuniones SET estado = :estado WHERE id = :id");
        $this->db->bind(':estado', $estado);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // Eliminar reunión
    public function delete($id) {
        $this->db->query("DELETE FROM reuniones WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
