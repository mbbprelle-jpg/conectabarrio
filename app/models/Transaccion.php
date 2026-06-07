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

    // Verificar si un socio ya pagó o fue eximido de un mes específico (en una organización)
    public function checkPagoSocio($socioId, $mesPagado, $juntaId = null) {
        $sql = "SELECT * FROM transacciones 
                         WHERE socio_id = :socio_id 
                         AND categoria IN ('Cuota Socio', 'Cuota Condonada') 
                         AND mes_pagado = :mes_pagado";
        if ($juntaId !== null) {
            $sql .= " AND junta_id = :junta_id";
        }
        $sql .= " LIMIT 1";
        $this->db->query($sql);
        $this->db->bind(':socio_id', $socioId);
        $this->db->bind(':mes_pagado', $mesPagado);
        if ($juntaId !== null) {
            $this->db->bind(':junta_id', $juntaId);
        }
        
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

    // Obtener los pagos realizados por un socio en una organización
    public function getPagosBySocio($socioId, $juntaId = null) {
        $sql = "SELECT t.*, r.nombre as admin_nombre 
                         FROM transacciones t 
                         LEFT JOIN usuarios r ON t.registrado_por = r.id 
                         WHERE t.socio_id = :socio_id AND t.categoria = 'Cuota Socio'";
        if ($juntaId !== null) {
            $sql .= " AND t.junta_id = :junta_id";
        }
        $sql .= " ORDER BY t.mes_pagado DESC";
        $this->db->query($sql);
        $this->db->bind(':socio_id', $socioId);
        if ($juntaId !== null) {
            $this->db->bind(':junta_id', $juntaId);
        }
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

    public function getAniosDisponiblesFlujo(int $juntaId, string $mesInicio): array {
        $startYear = (int)substr($mesInicio, 0, 4);
        $currentYear = (int)date('Y');

        $this->db->query('SELECT DISTINCT YEAR(fecha) AS anio FROM transacciones
            WHERE junta_id = :junta_id ORDER BY anio ASC');
        $this->db->bind(':junta_id', $juntaId);
        $rows = $this->db->resultSet();

        $years = [$startYear];
        foreach ($rows as $row) {
            if (!empty($row->anio)) {
                $years[] = (int)$row->anio;
            }
        }
        $years[] = $currentYear;

        $years = array_values(array_unique($years));
        sort($years);
        return $years;
    }

    /**
     * Matriz anual: filas por categoría (ingresos/egresos) × meses 1-12 + total fila.
     */
    public function getFlujoCajaMatrizAnual(int $juntaId, int $anio, string $mesInicio, FinanzaConcepto $conceptoModel): array {
        $mesInicioYear = (int)substr($mesInicio, 0, 4);
        $mesInicioMonth = (int)substr($mesInicio, 5, 2);

        $this->db->query("SELECT tipo, categoria, MONTH(fecha) AS mes_num, SUM(monto) AS total
            FROM transacciones
            WHERE junta_id = :junta_id AND fecha >= :desde AND fecha <= :hasta
            GROUP BY tipo, categoria, MONTH(fecha)
            ORDER BY tipo DESC, categoria ASC, mes_num ASC");
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':desde', $anio . '-01-01');
        $this->db->bind(':hasta', $anio . '-12-31');
        $raw = $this->db->resultSet();

        $dataMap = [];
        foreach ($raw as $row) {
            $key = $row->tipo . '|' . $row->categoria;
            if (!isset($dataMap[$key])) {
                $dataMap[$key] = [
                    'tipo' => $row->tipo,
                    'categoria' => $row->categoria,
                    'meses' => array_fill(1, 12, 0),
                    'total' => 0,
                ];
            }
            $m = (int)$row->mes_num;
            $val = (int)$row->total;
            $dataMap[$key]['meses'][$m] = $val;
            $dataMap[$key]['total'] += $val;
        }

        $conceptoModel->ensureDefaults($juntaId);
        $ordenIngreso = ['Cuota Socio'];
        foreach ($conceptoModel->getByJunta($juntaId, 'ingreso', false) as $c) {
            $ordenIngreso[] = $c->nombre;
        }
        $ordenEgreso = [];
        foreach ($conceptoModel->getByJunta($juntaId, 'egreso', false) as $c) {
            $ordenEgreso[] = $c->nombre;
        }

        foreach ($dataMap as $item) {
            if ($item['tipo'] === 'ingreso' && !in_array($item['categoria'], $ordenIngreso, true)) {
                $ordenIngreso[] = $item['categoria'];
            }
            if ($item['tipo'] === 'egreso' && !in_array($item['categoria'], $ordenEgreso, true)) {
                $ordenEgreso[] = $item['categoria'];
            }
        }

        $buildFilas = function (array $orden, string $tipo) use ($dataMap) {
            $filas = [];
            foreach ($orden as $nombre) {
                $key = $tipo . '|' . $nombre;
                $filas[] = $dataMap[$key] ?? [
                    'tipo' => $tipo,
                    'categoria' => $nombre,
                    'meses' => array_fill(1, 12, 0),
                    'total' => 0,
                ];
            }
            return $filas;
        };

        $filasIngreso = $buildFilas($ordenIngreso, 'ingreso');
        $filasEgreso = $buildFilas($ordenEgreso, 'egreso');

        $totalesIngresoMes = array_fill(1, 12, 0);
        $totalesEgresoMes = array_fill(1, 12, 0);
        foreach ($filasIngreso as $f) {
            for ($m = 1; $m <= 12; $m++) {
                $totalesIngresoMes[$m] += $f['meses'][$m];
            }
        }
        foreach ($filasEgreso as $f) {
            for ($m = 1; $m <= 12; $m++) {
                $totalesEgresoMes[$m] += $f['meses'][$m];
            }
        }

        $netoMes = [];
        $totalIngresosAnio = 0;
        $totalEgresosAnio = 0;
        for ($m = 1; $m <= 12; $m++) {
            $netoMes[$m] = $totalesIngresoMes[$m] - $totalesEgresoMes[$m];
            $totalIngresosAnio += $totalesIngresoMes[$m];
            $totalEgresosAnio += $totalesEgresoMes[$m];
        }

        $mesDesde = ($anio === $mesInicioYear) ? $mesInicioMonth : 1;
        $mesHasta = ($anio === (int)date('Y')) ? (int)date('n') : 12;

        return [
            'anio' => $anio,
            'mes_desde' => $mesDesde,
            'mes_hasta' => $mesHasta,
            'secciones' => [
                ['tipo' => 'ingreso', 'titulo' => 'Ingresos', 'filas' => $filasIngreso],
                ['tipo' => 'egreso', 'titulo' => 'Egresos', 'filas' => $filasEgreso],
            ],
            'totales_ingreso_mes' => $totalesIngresoMes,
            'totales_egreso_mes' => $totalesEgresoMes,
            'neto_mes' => $netoMes,
            'total_ingresos_anio' => $totalIngresosAnio,
            'total_egresos_anio' => $totalEgresosAnio,
            'neto_anio' => $totalIngresosAnio - $totalEgresosAnio,
        ];
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

    // Obtener transacciones de un socio en la organización activa (cuotas, donaciones, etc.)
    public function getTransaccionesBySocio($socioId, $juntaId = null) {
        $sql = "SELECT t.*, r.nombre as admin_nombre 
                         FROM transacciones t 
                         LEFT JOIN usuarios r ON t.registrado_por = r.id 
                         WHERE t.socio_id = :socio_id";
        if ($juntaId !== null) {
            $sql .= " AND t.junta_id = :junta_id";
        }
        $sql .= " ORDER BY t.fecha DESC, t.id DESC";
        $this->db->query($sql);
        $this->db->bind(':socio_id', $socioId);
        if ($juntaId !== null) {
            $this->db->bind(':junta_id', $juntaId);
        }
        return $this->db->resultSet();
    }
}
