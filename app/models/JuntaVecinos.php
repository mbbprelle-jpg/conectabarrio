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

    public function updateJunta(int $id, array $data): bool {
        $sql = "UPDATE juntas_vecinos SET nombre = :nombre, tipo = :tipo, direccion = :direccion, comuna = :comuna";
        if (array_key_exists('lat_sede', $data)) {
            $sql .= ', lat_sede = :lat_sede, lng_sede = :lng_sede';
        }
        $sql .= ' WHERE id = :id';
        $this->db->query($sql);
        $this->db->bind(':nombre', $data['nombre']);
        $this->db->bind(':tipo', $data['tipo']);
        $this->db->bind(':direccion', $data['direccion']);
        $this->db->bind(':comuna', $data['comuna']);
        if (array_key_exists('lat_sede', $data)) {
            $this->db->bind(':lat_sede', $data['lat_sede']);
            $this->db->bind(':lng_sede', $data['lng_sede']);
        }
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    private static $hasMapaSociosColumn = null;

    public function hasMapaSociosColumn(): bool {
        if (self::$hasMapaSociosColumn === null) {
            try {
                $this->db->query('SELECT mapa_socios_habilitado FROM juntas_vecinos LIMIT 1');
                $this->db->execute();
                self::$hasMapaSociosColumn = true;
            } catch (Exception $e) {
                self::$hasMapaSociosColumn = false;
            }
        }
        return self::$hasMapaSociosColumn;
    }

    public function updateMapaSociosHabilitado(int $id, bool $enabled): bool {
        if (!$this->hasMapaSociosColumn()) {
            return false;
        }
        $this->db->query('UPDATE juntas_vecinos SET mapa_socios_habilitado = :habilitado WHERE id = :id');
        $this->db->bind(':habilitado', $enabled ? 1 : 0);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    private static $hasSaldoInicialColumn = null;

    public function hasSaldoInicialColumn(): bool {
        if (self::$hasSaldoInicialColumn === null) {
            try {
                $this->db->query('SELECT saldo_inicial FROM juntas_vecinos LIMIT 1');
                $this->db->execute();
                self::$hasSaldoInicialColumn = true;
            } catch (Exception $e) {
                self::$hasSaldoInicialColumn = false;
            }
        }
        return self::$hasSaldoInicialColumn;
    }

    public function getSaldoInicial(int $id): ?int {
        if (!$this->hasSaldoInicialColumn()) {
            return null;
        }
        $this->db->query('SELECT saldo_inicial FROM juntas_vecinos WHERE id = :id LIMIT 1');
        $this->db->bind(':id', $id);
        $row = $this->db->single();
        if (!$row || $row->saldo_inicial === null) {
            return null;
        }
        return (int)$row->saldo_inicial;
    }

    public function setSaldoInicial(int $id, int $monto): bool {
        if (!$this->hasSaldoInicialColumn()) {
            return false;
        }
        $this->db->query('UPDATE juntas_vecinos SET saldo_inicial = :saldo, saldo_inicial_actualizado_at = NOW() WHERE id = :id');
        $this->db->bind(':saldo', max(0, $monto));
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }
}
