<?php
class CuotaConfig extends Model {
    
    // Obtener la configuración de cuota vigente para un mes específico
    public function getCuotaVigente($juntaId, $mesConsulta) {
        // mesConsulta en formato YYYY-MM
        $this->db->query("SELECT * FROM configuracion_cuotas 
                         WHERE junta_id = :junta_id AND mes_inicio <= :mes_consulta 
                         ORDER BY mes_inicio DESC LIMIT 1");
        
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':mes_consulta', $mesConsulta);
        
        $row = $this->db->single();
        
        // Si no hay configuración para ese mes (ej. consulta meses previos a la primera cuota),
        // obtener la configuración más antigua disponible
        if (!$row) {
            $this->db->query("SELECT * FROM configuracion_cuotas 
                             WHERE junta_id = :junta_id 
                             ORDER BY mes_inicio ASC LIMIT 1");
            $this->db->bind(':junta_id', $juntaId);
            $row = $this->db->single();
        }
        
        return $row ? $row : null;
    }

    // Registrar una nueva configuración de cuota (o reajuste)
    public function createConfig($data) {
        $this->db->query("INSERT INTO configuracion_cuotas (junta_id, monto, mes_inicio) VALUES (:junta_id, :monto, :mes_inicio)");
        $this->db->bind(':junta_id', $data['junta_id']);
        $this->db->bind(':monto', $data['monto']);
        $this->db->bind(':mes_inicio', $data['mes_inicio']); // YYYY-MM

        return $this->db->execute();
    }

    // Obtener el historial completo de configuraciones de cuotas de una Junta
    public function getHistoryByJunta($juntaId) {
        $this->db->query("SELECT * FROM configuracion_cuotas 
                         WHERE junta_id = :junta_id 
                         ORDER BY mes_inicio DESC");
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    // Eliminar una configuración de cuota
    public function delete($id) {
        $this->db->query("DELETE FROM configuracion_cuotas WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
