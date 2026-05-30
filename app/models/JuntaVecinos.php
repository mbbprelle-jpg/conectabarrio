<?php
class JuntaVecinos extends Model {
    
    // Obtener todas las Juntas de Vecinos
    public function getJuntas() {
        $this->db->query("SELECT j.*, 
                         (SELECT COUNT(*) FROM usuarios WHERE junta_id = j.id AND rol = 'socio') as total_socios,
                         (SELECT nombre FROM usuarios WHERE junta_id = j.id AND rol = 'admin' LIMIT 1) as admin_nombre
                         FROM juntas_vecinos j ORDER BY j.nombre ASC");
        return $this->db->resultSet();
    }

    // Obtener una Junta de Vecinos por su ID
    public function getJuntaById($id) {
        $this->db->query("SELECT * FROM juntas_vecinos WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Obtener una Junta de Vecinos por su RUT
    public function getJuntaByRut($rut) {
        $this->db->query("SELECT * FROM juntas_vecinos WHERE rut_junta = :rut");
        $this->db->bind(':rut', $rut);
        return $this->db->single();
    }

    // Crear una nueva Junta de Vecinos
    public function createJunta($data) {
        $this->db->query("INSERT INTO juntas_vecinos (nombre, rut_junta, direccion, comuna) VALUES (:nombre, :rut_junta, :direccion, :comuna)");
        $this->db->bind(':nombre', $data['nombre']);
        $this->db->bind(':rut_junta', $data['rut_junta']);
        $this->db->bind(':direccion', $data['direccion']);
        $this->db->bind(':comuna', $data['comuna']);

        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    // Actualizar plan comercial, precio anual e inicio de suscripción
    public function updatePlanAndPrice($id, $plan, $precioAnual, $mesInicioSuscripcion = null) {
        if ($mesInicioSuscripcion) {
            $this->db->query("UPDATE juntas_vecinos SET plan = :plan, precio_anual = :precio_anual, mes_inicio_suscripcion = :mes_inicio_suscripcion WHERE id = :id");
            $this->db->bind(':mes_inicio_suscripcion', $mesInicioSuscripcion);
        } else {
            $this->db->query("UPDATE juntas_vecinos SET plan = :plan, precio_anual = :precio_anual WHERE id = :id");
        }
        $this->db->bind(':plan', $plan);
        $this->db->bind(':precio_anual', $precioAnual);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // Obtener estadísticas globales para Perfil Maestro
    public function getStatsGlobal() {
        $stats = [];
        
        // Total Juntas
        $this->db->query("SELECT COUNT(*) as total FROM juntas_vecinos");
        $stats['total_juntas'] = $this->db->single()->total;

        // Total Socios
        $this->db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'socio'");
        $stats['total_socios'] = $this->db->single()->total;

        // Total Admins
        $this->db->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'admin'");
        $stats['total_admins'] = $this->db->single()->total;

        // Desglose por Tipo de Organización
        $this->db->query("SELECT tipo, COUNT(*) as total FROM juntas_vecinos GROUP BY tipo");
        $res = $this->db->resultSet();
        $stats['juntas_de_vecinos'] = 0;
        $stats['comites'] = 0;
        $stats['organizaciones'] = 0;

        foreach ($res as $row) {
            $tipo = $row->tipo ?? '';
            if ($tipo === 'Junta de Vecinos') {
                $stats['juntas_de_vecinos'] = (int)$row->total;
            } elseif ($tipo === 'Comité') {
                $stats['comites'] = (int)$row->total;
            } elseif ($tipo === 'Organización') {
                $stats['organizaciones'] = (int)$row->total;
            }
        }

        return $stats;
    }
}
