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

    /**
     * Mapa socio_id|mes_pagado de cuotas ya registradas o exentas en el rango.
     * @return array<string, true>
     */
    public function mapCuotasRegistradasEnMeses(int $juntaId, array $meses): array {
        if (empty($meses)) {
            return [];
        }
        $holders = [];
        foreach ($meses as $i => $mes) {
            $holders[] = ':mes_' . $i;
        }
        $this->db->query("SELECT socio_id, mes_pagado FROM transacciones
            WHERE junta_id = :junta_id
            AND categoria IN ('Cuota Socio', 'Cuota Condonada')
            AND mes_pagado IN (" . implode(', ', $holders) . ")
            AND socio_id IS NOT NULL");
        $this->db->bind(':junta_id', $juntaId);
        foreach ($meses as $i => $mes) {
            $this->db->bind(':mes_' . $i, $mes);
        }
        $map = [];
        foreach ($this->db->resultSet() as $row) {
            if (!empty($row->socio_id) && !empty($row->mes_pagado)) {
                $map[(int)$row->socio_id . '|' . $row->mes_pagado] = true;
            }
        }
        return $map;
    }

    public static function socioNombreCompleto($row): string {
        $parts = array_filter([
            trim($row->socio_nombre ?? ''),
            trim($row->socio_apellido_paterno ?? ''),
            trim($row->socio_apellido_materno ?? ''),
        ], static fn($p) => $p !== '');
        return implode(' ', $parts);
    }

    // Obtener todas las transacciones de una Junta de Vecinos
    public function getTransaccionesByJunta($juntaId) {
        $this->db->query("SELECT t.*,
            u.nombre AS socio_nombre,
            u.apellido_paterno AS socio_apellido_paterno,
            u.apellido_materno AS socio_apellido_materno,
            u.rut AS socio_rut,
            r.nombre AS admin_nombre
                         FROM transacciones t 
                         LEFT JOIN usuarios u ON t.socio_id = u.id 
                         LEFT JOIN usuarios r ON t.registrado_por = r.id 
                         WHERE t.junta_id = :junta_id 
                         ORDER BY t.fecha DESC, t.id DESC");
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    public function getTransaccionByIdAndJunta(int $id, int $juntaId) {
        $this->db->query("SELECT t.*,
            u.nombre AS socio_nombre,
            u.apellido_paterno AS socio_apellido_paterno,
            u.apellido_materno AS socio_apellido_materno,
            u.rut AS socio_rut
            FROM transacciones t
            LEFT JOIN usuarios u ON t.socio_id = u.id
            WHERE t.id = :id AND t.junta_id = :junta_id
            LIMIT 1");
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->single();
    }

    public function updateTransaccion(int $id, int $juntaId, array $data): bool {
        $this->db->query("UPDATE transacciones SET
            tipo = :tipo,
            categoria = :categoria,
            monto = :monto,
            descripcion = :descripcion,
            fecha = :fecha,
            socio_id = :socio_id,
            mes_pagado = :mes_pagado
            WHERE id = :id AND junta_id = :junta_id");
        $this->db->bind(':tipo', $data['tipo']);
        $this->db->bind(':categoria', $data['categoria']);
        $this->db->bind(':monto', $data['monto']);
        $this->db->bind(':descripcion', $data['descripcion'] ?? null);
        $this->db->bind(':fecha', $data['fecha']);
        $this->db->bind(':socio_id', $data['socio_id'] ?? null);
        $this->db->bind(':mes_pagado', $data['mes_pagado'] ?? null);
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->execute();
    }

    public function deleteTransaccion(int $id, int $juntaId): bool {
        $this->db->query("DELETE FROM transacciones WHERE id = :id AND junta_id = :junta_id");
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->execute();
    }

    public function checkPagoSocioExcluding(int $socioId, string $mesPagado, int $juntaId, int $excludeId): bool {
        $this->db->query("SELECT id FROM transacciones
            WHERE socio_id = :socio_id
            AND junta_id = :junta_id
            AND categoria IN ('Cuota Socio', 'Cuota Condonada')
            AND mes_pagado = :mes_pagado
            AND id != :exclude_id
            LIMIT 1");
        $this->db->bind(':socio_id', $socioId);
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':mes_pagado', $mesPagado);
        $this->db->bind(':exclude_id', $excludeId);
        return (bool)$this->db->single();
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

    public function getNetoEntreFechas(int $juntaId, string $desde, string $hasta): int {
        $this->db->query("SELECT
            COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END), 0)
            - COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END), 0) AS neto
            FROM transacciones
            WHERE junta_id = :junta_id AND fecha >= :desde AND fecha <= :hasta");
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':desde', $desde);
        $this->db->bind(':hasta', $hasta);
        $row = $this->db->single();
        return $row ? (int)$row->neto : 0;
    }

    private function getSaldoAperturaAnio(int $juntaId, int $anio, string $mesInicio, ?int $saldoInicial): int {
        $mesInicioYear = (int)substr($mesInicio, 0, 4);

        if ($anio <= $mesInicioYear) {
            return 0;
        }

        $base = $saldoInicial ?? 0;
        $inicioOrg = $mesInicio . '-01';
        $finAnioAnterior = ($anio - 1) . '-12-31';

        return $base + $this->getNetoEntreFechas($juntaId, $inicioOrg, $finAnioAnterior);
    }

    private function getMesesVisiblesFlujo(int $anio, string $mesInicio): array {
        $mesInicioYear = (int)substr($mesInicio, 0, 4);
        $mesInicioMonth = (int)substr($mesInicio, 5, 2);
        $anioActual = (int)date('Y');
        $mesActual = (int)date('n');

        if ($anio < $mesInicioYear || $anio > $anioActual) {
            return [];
        }

        $desde = ($anio === $mesInicioYear) ? $mesInicioMonth : 1;
        $hasta = ($anio === $anioActual) ? $mesActual : 12;

        $meses = [];
        for ($m = $desde; $m <= $hasta; $m++) {
            $meses[] = $m;
        }
        return $meses;
    }

    private function sumMesesVisibles(array $valoresPorMes, array $mesesVisibles): int {
        $total = 0;
        foreach ($mesesVisibles as $m) {
            $total += (int)($valoresPorMes[$m] ?? 0);
        }
        return $total;
    }

    /**
     * Matriz anual: filas por categoría (ingresos/egresos) × meses visibles + total periodo.
     */
    public function getFlujoCajaMatrizAnual(int $juntaId, int $anio, string $mesInicio, FinanzaConcepto $conceptoModel, ?int $saldoInicial = null): array {
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
        $ordenIngreso = $conceptoModel->getOrdenNombres($juntaId, 'ingreso');
        $ordenEgreso = $conceptoModel->getOrdenNombres($juntaId, 'egreso');

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

        $mesesVisibles = $this->getMesesVisiblesFlujo($anio, $mesInicio);
        $mesDesde = !empty($mesesVisibles) ? $mesesVisibles[0] : 1;
        $mesHasta = !empty($mesesVisibles) ? $mesesVisibles[count($mesesVisibles) - 1] : 0;

        foreach ($filasIngreso as &$fila) {
            $fila['total'] = $this->sumMesesVisibles($fila['meses'], $mesesVisibles);
        }
        unset($fila);
        foreach ($filasEgreso as &$fila) {
            $fila['total'] = $this->sumMesesVisibles($fila['meses'], $mesesVisibles);
        }
        unset($fila);

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
        for ($m = 1; $m <= 12; $m++) {
            $netoMes[$m] = $totalesIngresoMes[$m] - $totalesEgresoMes[$m];
        }

        $totalIngresosAnio = $this->sumMesesVisibles($totalesIngresoMes, $mesesVisibles);
        $totalEgresosAnio = $this->sumMesesVisibles($totalesEgresoMes, $mesesVisibles);

        $saldoAnteriorMes = array_fill(1, 12, null);
        $saldoFinalMes = array_fill(1, 12, null);
        $saldoAperturaAnio = $this->getSaldoAperturaAnio($juntaId, $anio, $mesInicio, $saldoInicial);
        $cierreMesPrevio = $saldoAperturaAnio;
        $saldoContableFinAnio = $saldoAperturaAnio;

        foreach ($mesesVisibles as $m) {
            if ($anio === $mesInicioYear && $m === $mesInicioMonth) {
                $saldoAnteriorMes[$m] = $saldoInicial ?? 0;
            } else {
                $saldoAnteriorMes[$m] = $cierreMesPrevio;
            }

            $saldoFinalMes[$m] = $saldoAnteriorMes[$m] + $totalesIngresoMes[$m] - $totalesEgresoMes[$m];
            $cierreMesPrevio = $saldoFinalMes[$m];
            $saldoContableFinAnio = $saldoFinalMes[$m];
        }

        return [
            'anio' => $anio,
            'mes_desde' => $mesDesde,
            'mes_hasta' => $mesHasta,
            'meses_visibles' => $mesesVisibles,
            'saldo_inicial' => $saldoInicial,
            'saldo_anterior_mes' => $saldoAnteriorMes,
            'saldo_final_mes' => $saldoFinalMes,
            'saldo_contable_fin_anio' => $saldoContableFinAnio,
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

    /**
     * Reporte de movimientos con filtros y datos del socio (RUT/nombre) para cuotas.
     *
     * @param array{fecha_desde?:string,fecha_hasta?:string,tipo?:string,categoria?:string} $filters
     * @return array{rows: array, totales: array{ingresos:int,egresos:int,neto:int,cuotas:int,cantidad:int}}
     */
    public function getReporteMovimientos(int $juntaId, array $filters = []): array {
        $where = ['t.junta_id = :junta_id'];
        $binds = [':junta_id' => $juntaId];

        $fechaDesde = trim((string)($filters['fecha_desde'] ?? ''));
        $fechaHasta = trim((string)($filters['fecha_hasta'] ?? ''));
        $tipo = trim((string)($filters['tipo'] ?? ''));
        $categoria = trim((string)($filters['categoria'] ?? ''));

        if ($fechaDesde !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaDesde)) {
            $where[] = 't.fecha >= :fecha_desde';
            $binds[':fecha_desde'] = $fechaDesde;
        }
        if ($fechaHasta !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaHasta)) {
            $where[] = 't.fecha <= :fecha_hasta';
            $binds[':fecha_hasta'] = $fechaHasta;
        }
        if (in_array($tipo, ['ingreso', 'egreso'], true)) {
            $where[] = 't.tipo = :tipo';
            $binds[':tipo'] = $tipo;
        }
        if ($categoria !== '') {
            $where[] = 't.categoria = :categoria';
            $binds[':categoria'] = $categoria;
        }

        $sqlWhere = implode(' AND ', $where);

        $this->db->query("SELECT t.*,
            u.nombre AS socio_nombre,
            u.apellido_paterno AS socio_apellido_paterno,
            u.apellido_materno AS socio_apellido_materno,
            u.rut AS socio_rut,
            r.nombre AS admin_nombre,
            r.apellido_paterno AS admin_apellido_paterno
            FROM transacciones t
            LEFT JOIN usuarios u ON t.socio_id = u.id
            LEFT JOIN usuarios r ON t.registrado_por = r.id
            WHERE {$sqlWhere}
            ORDER BY t.fecha ASC, t.id ASC");
        foreach ($binds as $key => $val) {
            $this->db->bind($key, $val);
        }
        $rows = $this->db->resultSet();

        $ingresos = 0;
        $egresos = 0;
        $cuotas = 0;
        foreach ($rows as $row) {
            $monto = (int)($row->monto ?? 0);
            if (($row->tipo ?? '') === 'ingreso') {
                $ingresos += $monto;
                if (($row->categoria ?? '') === 'Cuota Socio') {
                    $cuotas += $monto;
                }
            } elseif (($row->tipo ?? '') === 'egreso') {
                $egresos += $monto;
            }
        }

        return [
            'rows' => $rows,
            'totales' => [
                'ingresos' => $ingresos,
                'egresos' => $egresos,
                'neto' => $ingresos - $egresos,
                'cuotas' => $cuotas,
                'cantidad' => count($rows),
            ],
        ];
    }

    /** Categorías distintas usadas en movimientos de la junta (para filtro del reporte). */
    public function getCategoriasUsadas(int $juntaId): array {
        $this->db->query("SELECT DISTINCT categoria FROM transacciones
            WHERE junta_id = :junta_id AND categoria IS NOT NULL AND categoria <> ''
            ORDER BY categoria ASC");
        $this->db->bind(':junta_id', $juntaId);
        $out = [];
        foreach ($this->db->resultSet() as $row) {
            $out[] = $row->categoria;
        }
        return $out;
    }
}
