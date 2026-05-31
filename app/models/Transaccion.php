<?php
class Transaccion extends Model {
    
    // Crear una nueva transacción (Ingreso/Egreso)
    public function createTransaccion($data) {
        $this->db->query("INSERT INTO transacciones (junta_id, tipo, categoria, monto, descripcion, fecha, comprobante_url, socio_id, mes_pagado, registrado_por) 
                         VALUES (:junta_id, :tipo, :categoria, :monto, :descripcion, :fecha, :comprobante_url, :socio_id, :mes_pagado, :registrado_por)");
        
        $this->db->bind(':junta_id', $data['junta_id']);
        $this->db->bind(':tipo', $data['tipo']);
        $this->db->bind(':categoria', $data['categoria']);
        $this->db->bind(':monto', $data['monto']);
        $this->db->bind(':descripcion', $data['descripcion'] ?? null);
        $this->db->bind(':fecha', $data['fecha']);
        $this->db->bind(':comprobante_url', $data['comprobante_url'] ?? null);
        $this->db->bind(':socio_id', $data['socio_id'] ?? null);
        $this->db->bind(':mes_pagado', $data['mes_pagado'] ?? null);
        $this->db->bind(':registrado_por', $data['registrado_por']);

        return $this->db->execute();
    }

    // Verificar si un socio ya pagó o fue eximido de un mes específico
    public function checkPagoSocio($socioId, $mesPagado) {
        $this->db->query("SELECT * FROM transacciones 
                         WHERE socio_id = :socio_id 
                         AND categoria IN ('Cuota Socio', 'Cuota Condonada') 
                         AND mes_pagado = :mes_pagado LIMIT 1");
        $this->db->bind(':socio_id', $socioId);
        $this->db->bind(':mes_pagado', $mesPagado);
        
        return $this->db->single() ? true : false;
    }

    // Obtener todas las transacciones de una Junta de Vecinos
    public function getTransaccionesByJunta($juntaId) {
        $this->db->query("SELECT t.*, u.nombre as socio_nombre, r.nombre as admin_nombre 
                         FROM transacciones t 
                         LEFT JOIN usuarios u ON t.socio_id = u.id 
                         LEFT JOIN usuarios r ON t.registrado_por = r.id 
                         WHERE t.junta_id = :junta_id 
                         ORDER BY t.fecha DESC, t.id DESC");
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    // Obtener los pagos realizados por un socio específico
    public function getPagosBySocio($socioId) {
        $this->db->query("SELECT t.*, r.nombre as admin_nombre 
                         FROM transacciones t 
                         LEFT JOIN usuarios r ON t.registrado_por = r.id 
                         WHERE t.socio_id = :socio_id AND t.categoria = 'Cuota Socio' 
                         ORDER BY t.mes_pagado DESC");
        $this->db->bind(':socio_id', $socioId);
        return $this->db->resultSet();
    }

    // Obtener un comprobante de cuota por ID (recibo imprimible)
    public function getComprobanteById($id) {
        $this->db->query("SELECT t.*,
            u.nombre AS socio_nombre, u.rut AS socio_rut, u.email AS socio_email, u.id_socio,
            j.nombre AS junta_nombre, j.rut_junta AS junta_rut_id, j.direccion AS junta_direccion, j.comuna AS junta_comuna,
            r.nombre AS admin_nombre
            FROM transacciones t
            INNER JOIN usuarios u ON t.socio_id = u.id
            INNER JOIN juntas_vecinos j ON t.junta_id = j.id
            LEFT JOIN usuarios r ON t.registrado_por = r.id
            WHERE t.id = :id
            AND t.socio_id IS NOT NULL
            AND t.categoria IN ('Cuota Socio', 'Cuota Condonada')");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Alias retrocompatible
    public function getPagoById($id) {
        return $this->getComprobanteById($id);
    }

    // Obtener balance financiero consolidado para el Dashboard
    public function getBalanceConsolidado($juntaId) {
        $balance = [
            'ingresos' => 0,
            'egresos' => 0,
            'neto' => 0,
            'cuotas' => 0,
            'otros' => 0
        ];

        // Total Ingresos
        $this->db->query("SELECT SUM(monto) as total FROM transacciones WHERE junta_id = :junta_id AND tipo = 'ingreso'");
        $this->db->bind(':junta_id', $juntaId);
        $res = $this->db->single();
        $balance['ingresos'] = $res->total ?? 0;

        // Total Egresos
        $this->db->query("SELECT SUM(monto) as total FROM transacciones WHERE junta_id = :junta_id AND tipo = 'egreso'");
        $this->db->bind(':junta_id', $juntaId);
        $res = $this->db->single();
        $balance['egresos'] = $res->total ?? 0;

        // Balance Neto
        $balance['neto'] = $balance['ingresos'] - $balance['egresos'];

        // Total Ingresos por Cuotas de Socios
        $this->db->query("SELECT SUM(monto) as total FROM transacciones WHERE junta_id = :junta_id AND categoria = 'Cuota Socio'");
        $this->db->bind(':junta_id', $juntaId);
        $res = $this->db->single();
        $balance['cuotas'] = $res->total ?? 0;

        // Total Otros Ingresos
        $balance['otros'] = $balance['ingresos'] - $balance['cuotas'];

        return $balance;
    }

    // Obtener resumen mensual agrupado para Flujo de Caja (últimos 6 meses)
    public function getFlujoCajaHistorico($juntaId) {
        $this->db->query("SELECT 
                         DATE_FORMAT(fecha, '%Y-%m') as mes,
                         SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END) as ingresos,
                         SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END) as egresos
                         FROM transacciones 
                         WHERE junta_id = :junta_id 
                         GROUP BY DATE_FORMAT(fecha, '%Y-%m') 
                         ORDER BY mes ASC LIMIT 6");
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    // Obtener todas las transacciones asociadas a un socio (Cuotas, Condonaciones, Donaciones)
    public function getTransaccionesBySocio($socioId) {
        $this->db->query("SELECT t.*, r.nombre as admin_nombre 
                         FROM transacciones t 
                         LEFT JOIN usuarios r ON t.registrado_por = r.id 
                         WHERE t.socio_id = :socio_id 
                         ORDER BY t.fecha DESC, t.id DESC");
        $this->db->bind(':socio_id', $socioId);
        return $this->db->resultSet();
    }
}
