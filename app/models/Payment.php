<?php
class Payment extends Model {
    private static $hasMesPeriodo = null;
    private static $hasMetodoPago = null;
    private static $hasComprobantesTable = null;
    private static $hasComprobanteIdColumn = null;

    public static function metodoPagoLabels() {
        return [
            'transferencia' => 'Transferencia',
            'efectivo' => 'Efectivo',
            'webpay' => 'Webpay'
        ];
    }

    private function hasMetodoPagoColumn() {
        if (self::$hasMetodoPago === null) {
            try {
                $this->db->query('SELECT metodo_pago FROM payments LIMIT 1');
                $this->db->execute();
                self::$hasMetodoPago = true;
            } catch (Exception $e) {
                self::$hasMetodoPago = false;
            }
        }
        return self::$hasMetodoPago;
    }

    private function hasMesPeriodoColumn() {
        if (self::$hasMesPeriodo === null) {
            try {
                $this->db->query('SELECT mes_periodo FROM payments LIMIT 1');
                $this->db->execute();
                self::$hasMesPeriodo = true;
            } catch (Exception $e) {
                self::$hasMesPeriodo = false;
            }
        }
        return self::$hasMesPeriodo;
    }

    private function hasComprobantesTable() {
        if (self::$hasComprobantesTable === null) {
            try {
                $this->db->query('SELECT id FROM suscripcion_comprobantes LIMIT 1');
                $this->db->execute();
                self::$hasComprobantesTable = true;
            } catch (Exception $e) {
                self::$hasComprobantesTable = false;
            }
        }
        return self::$hasComprobantesTable;
    }

    private function hasComprobanteIdColumn() {
        if (self::$hasComprobanteIdColumn === null) {
            try {
                $this->db->query('SELECT comprobante_id FROM payments LIMIT 1');
                $this->db->execute();
                self::$hasComprobanteIdColumn = true;
            } catch (Exception $e) {
                self::$hasComprobanteIdColumn = false;
            }
        }
        return self::$hasComprobanteIdColumn;
    }

    private function createComprobante($orgId, $fechaPago, $metodoPago, $total) {
        $year = date('Y', strtotime($fechaPago));
        $prefix = 'CB-S-' . $year . '-';
        $this->db->query("SELECT correlativo FROM suscripcion_comprobantes
            WHERE correlativo LIKE :prefix ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $this->db->bind(':prefix', $prefix . '%');
        $last = $this->db->single();
        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last->correlativo, $matches)) {
            $next = (int)$matches[1] + 1;
        }
        $correlativo = $prefix . str_pad($next, 6, '0', STR_PAD_LEFT);
        $this->db->query("INSERT INTO suscripcion_comprobantes (correlativo, org_id, fecha_pago, metodo_pago, total_amount)
            VALUES (:correlativo, :org_id, :fecha_pago, :metodo_pago, :total_amount)");
        $this->db->bind(':correlativo', $correlativo);
        $this->db->bind(':org_id', $orgId);
        $this->db->bind(':fecha_pago', $fechaPago);
        $this->db->bind(':metodo_pago', $metodoPago ?: null);
        $this->db->bind(':total_amount', (int)$total);
        if (!$this->db->execute()) {
            return null;
        }
        return [
            'id' => $this->db->lastInsertId(),
            'correlativo' => $correlativo,
        ];
    }

    public static function monthlyAmountForOrg($junta) {
        if (!empty($junta->precio_anual) && (int)$junta->precio_anual > 0) {
            return (int)round((int)$junta->precio_anual / 12);
        }
        $plan = $junta->plan ?? 'basico';
        $defaults = ['basico' => 4990, 'mediano' => 7990, 'premium' => 9990];
        return $defaults[$plan] ?? 4990;
    }

    public function getByOrgMonth($orgId, $mes) {
        if ($this->hasMesPeriodoColumn()) {
            $this->db->query("SELECT * FROM payments
                WHERE org_id = :org_id
                AND (mes_periodo = :mes OR DATE_FORMAT(due_date, '%Y-%m') = :mes2)
                LIMIT 1");
        } else {
            $this->db->query("SELECT * FROM payments
                WHERE org_id = :org_id
                AND DATE_FORMAT(due_date, '%Y-%m') = :mes
                LIMIT 1");
        }
        $this->db->bind(':org_id', $orgId);
        $this->db->bind(':mes', $mes);
        if ($this->hasMesPeriodoColumn()) {
            $this->db->bind(':mes2', $mes);
        }
        return $this->db->single();
    }

    public function isMonthPaid($orgId, $mes) {
        $record = $this->getByOrgMonth($orgId, $mes);
        return $record && $record->status === 'paid';
    }

    public function registerMonths($orgId, array $mesAmounts, $fechaPago, $metodoPago = null) {
        $useMesPeriodo = $this->hasMesPeriodoColumn();
        $useMetodoPago = $this->hasMetodoPagoColumn() && $metodoPago;
        $useComprobante = $this->hasComprobantesTable() && $this->hasComprobanteIdColumn();

        $pending = [];
        foreach ($mesAmounts as $mes => $amount) {
            $mes = trim((string)$mes);
            $amount = max(0, (int)$amount);
            if (!preg_match('/^\d{4}-\d{2}$/', $mes) || $this->isMonthPaid($orgId, $mes)) {
                continue;
            }
            $pending[$mes] = $amount;
        }

        if (empty($pending)) {
            return ['registered' => 0, 'total' => 0, 'correlativo' => null];
        }

        $registered = 0;
        $total = 0;
        $correlativo = null;
        $comprobanteId = null;

        try {
            $this->db->beginTransaction();

            if ($useComprobante) {
                $comprobante = $this->createComprobante($orgId, $fechaPago, $metodoPago, array_sum($pending));
                if (!$comprobante) {
                    throw new Exception('No se pudo generar el correlativo del comprobante.');
                }
                $comprobanteId = $comprobante['id'];
                $correlativo = $comprobante['correlativo'];
            }

            foreach ($pending as $mes => $amount) {
                $existing = $this->getByOrgMonth($orgId, $mes);
                $dueDate = date('Y-m-t', strtotime($mes . '-01'));

                if ($existing) {
                    $sets = ['amount = :amount', 'due_date = :due_date', 'paid_at = :paid_at', "status = 'paid'"];
                    if ($useMesPeriodo) {
                        $sets[] = 'mes_periodo = :mes_periodo';
                    }
                    if ($useMetodoPago) {
                        $sets[] = 'metodo_pago = :metodo_pago';
                    }
                    if ($useComprobante) {
                        $sets[] = 'comprobante_id = :comprobante_id';
                    }
                    $this->db->query('UPDATE payments SET ' . implode(', ', $sets) . ' WHERE id = :id');
                    if ($useMesPeriodo) {
                        $this->db->bind(':mes_periodo', $mes);
                    }
                    if ($useMetodoPago) {
                        $this->db->bind(':metodo_pago', $metodoPago);
                    }
                    if ($useComprobante) {
                        $this->db->bind(':comprobante_id', $comprobanteId);
                    }
                    $this->db->bind(':amount', $amount);
                    $this->db->bind(':due_date', $dueDate);
                    $this->db->bind(':paid_at', $fechaPago);
                    $this->db->bind(':id', $existing->id);
                } else {
                    $columns = ['org_id'];
                    $values = [':org_id'];
                    if ($useMesPeriodo) {
                        $columns[] = 'mes_periodo';
                        $values[] = ':mes_periodo';
                    }
                    $columns = array_merge($columns, ['amount', 'due_date', 'paid_at']);
                    $values = array_merge($values, [':amount', ':due_date', ':paid_at']);
                    if ($useMetodoPago) {
                        $columns[] = 'metodo_pago';
                        $values[] = ':metodo_pago';
                    }
                    if ($useComprobante) {
                        $columns[] = 'comprobante_id';
                        $values[] = ':comprobante_id';
                    }
                    $columns[] = 'status';
                    $values[] = "'paid'";
                    $this->db->query('INSERT INTO payments (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ')');
                    $this->db->bind(':org_id', $orgId);
                    if ($useMesPeriodo) {
                        $this->db->bind(':mes_periodo', $mes);
                    }
                    $this->db->bind(':amount', $amount);
                    $this->db->bind(':due_date', $dueDate);
                    $this->db->bind(':paid_at', $fechaPago);
                    if ($useMetodoPago) {
                        $this->db->bind(':metodo_pago', $metodoPago);
                    }
                    if ($useComprobante) {
                        $this->db->bind(':comprobante_id', $comprobanteId);
                    }
                }

                if ($this->db->execute()) {
                    $registered++;
                    $total += $amount;
                }
            }

            if ($registered === 0) {
                $this->db->rollBack();
                return ['registered' => 0, 'total' => 0, 'correlativo' => null];
            }

            $this->db->commit();
        } catch (Exception $e) {
            try {
                $this->db->rollBack();
            } catch (Exception $ignored) {
            }
            throw $e;
        }

        return ['registered' => $registered, 'total' => $total, 'correlativo' => $correlativo];
    }

    public function getAllWithOrg() {
        $orderExpr = $this->hasMesPeriodoColumn()
            ? "COALESCE(p.mes_periodo, DATE_FORMAT(p.due_date, '%Y-%m'))"
            : "DATE_FORMAT(p.due_date, '%Y-%m')";
        $joinComprobante = $this->hasComprobantesTable() && $this->hasComprobanteIdColumn()
            ? ' LEFT JOIN suscripcion_comprobantes sc ON sc.id = p.comprobante_id'
            : '';
        $selectCorrelativo = $joinComprobante ? ', sc.correlativo AS comprobante_correlativo' : '';

        $this->db->query("SELECT p.*, j.nombre AS org_nombre{$selectCorrelativo}
            FROM payments p
            INNER JOIN juntas_vecinos j ON j.id = p.org_id{$joinComprobante}
            ORDER BY {$orderExpr} DESC, p.id DESC");
        return $this->db->resultSet();
    }

    public function summarizeGlobal() {
        $this->db->query("SELECT
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count
            FROM payments");
        $res = $this->db->single();
        return [
            'paid' => $res ? (int)$res->paid_count : 0,
            'overdue' => $res ? (int)$res->overdue_count : 0,
            'pending' => $res ? (int)$res->pending_count : 0
        ];
    }
}
