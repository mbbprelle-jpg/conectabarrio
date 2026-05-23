<?php
class Asistencia extends Model {
    
    // Obtener la lista de asistencia para una reunión (incluye a todos los socios de la junta)
    public function getAsistenciaByReunion($reunionId, $juntaId) {
        $this->db->query("SELECT u.id as socio_id, u.nombre, u.rut, COALESCE(a.asistio, 0) as asistio 
                         FROM usuarios u 
                         LEFT JOIN asistencia a ON u.id = a.socio_id AND a.reunion_id = :reunion_id 
                         WHERE u.junta_id = :junta_id AND u.rol = 'socio' AND u.estado = 1 
                         ORDER BY u.nombre ASC");
        
        $this->db->bind(':reunion_id', $reunionId);
        $this->db->bind(':junta_id', $juntaId);
        
        return $this->db->resultSet();
    }

    // Registrar o actualizar asistencia de forma atómica
    public function saveAsistencia($reunionId, $socioId, $asistio) {
        $this->db->query("INSERT INTO asistencia (reunion_id, socio_id, asistio) 
                         VALUES (:reunion_id, :socio_id, :asistio) 
                         ON DUPLICATE KEY UPDATE asistio = :asistio_update");
        
        $this->db->bind(':reunion_id', $reunionId);
        $this->db->bind(':socio_id', $socioId);
        $this->db->bind(':asistio', $asistio);
        $this->db->bind(':asistio_update', $asistio);

        return $this->db->execute();
    }

    // Eliminar registros de asistencia para una reunión
    public function deleteAsistenciaByReunion($reunionId) {
        $this->db->query("DELETE FROM asistencia WHERE reunion_id = :reunion_id");
        $this->db->bind(':reunion_id', $reunionId);
        return $this->db->execute();
    }

    // Obtener resumen de asistencia promedio de socios para el Dashboard
    public function getPromedioAsistencia($juntaId) {
        $this->db->query("SELECT 
                         (SELECT COUNT(*) FROM asistencia a 
                          INNER JOIN reuniones r ON a.reunion_id = r.id 
                          WHERE r.junta_id = :junta_id1 AND a.asistio = 1) as total_presentes,
                         (SELECT COUNT(*) FROM asistencia a 
                          INNER JOIN reuniones r ON a.reunion_id = r.id 
                          WHERE r.junta_id = :junta_id2) as total_oportunidades");
        
        $this->db->bind(':junta_id1', $juntaId);
        $this->db->bind(':junta_id2', $juntaId);
        
        $res = $this->db->single();
        
        if (!$res || $res->total_oportunidades == 0) {
            return 0;
        }
        
        return round(($res->total_presentes / $res->total_oportunidades) * 100);
    }
}
