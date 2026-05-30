<?php
class Payment extends Model {

    public static function monthlyAmountForOrg($junta) {
        if (!empty($junta->precio_anual) && (int)$junta->precio_anual > 0) {
            return (int)round((int)$junta->precio_anual / 12);
        }
        $plan = $junta->plan ?? 'basico';
        $defaults = ['basico' => 4990, 'mediano' => 7990, 'premium' => 9990];
        return $defaults[$plan] ?? 4990;
    }

    public function getByOrgMonth($orgId, $mes) {
        $this->db->query("SELECT * FROM payments
            WHERE org_id = :org_id
            AND (mes_periodo = :mes OR DATE_FORMAT(due_date, '%Y-%m') = :mes2)
            LIMIT 1");
        $this->db->bind(':org_id', $orgId);
        $this->db->bind(':mes', $mes);
        $this->db->bind(':mes2', $mes);
        return $this->db->single();
    }

    public function isMonthPaid($orgId, $mes) {
        $record = $this->getByOrgMonth($orgId, $mes);
        return $record && $record->status === 'paid';
    }

    public function registerMonths($orgId, array $meses, $fechaPago, $monthlyAmount) {
        $registered = 0;
        foreach ($meses as $mes) {
            $mes = trim($mes);
            if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
                continue;
            }
            if ($this->isMonthPaid($orgId, $mes)) {
                continue;
            }

            $existing = $this->getByOrgMonth($orgId, $mes);
            $dueDate = date('Y-m-t', strtotime($mes . '-01'));

            if ($existing) {
                $this->db->query("UPDATE payments
                    SET amount = :amount, due_date = :due_date, paid_at = :paid_at, status = 'paid', mes_periodo = :mes_periodo
                    WHERE id = :id");
                $this->db->bind(':amount', $monthlyAmount);
                $this->db->bind(':due_date', $dueDate);
                $this->db->bind(':paid_at', $fechaPago);
                $this->db->bind(':mes_periodo', $mes);
                $this->db->bind(':id', $existing->id);
            } else {
                $this->db->query("INSERT INTO payments (org_id, mes_periodo, amount, due_date, paid_at, status)
                    VALUES (:org_id, :mes_periodo, :amount, :due_date, :paid_at, 'paid')");
                $this->db->bind(':org_id', $orgId);
                $this->db->bind(':mes_periodo', $mes);
                $this->db->bind(':amount', $monthlyAmount);
                $this->db->bind(':due_date', $dueDate);
                $this->db->bind(':paid_at', $fechaPago);
            }

            if ($this->db->execute()) {
                $registered++;
            }
        }
        return $registered;
    }

    public function getAllWithOrg() {
        $this->db->query("SELECT p.*, j.nombre AS org_nombre
            FROM payments p
            INNER JOIN juntas_vecinos j ON j.id = p.org_id
            ORDER BY COALESCE(p.mes_periodo, DATE_FORMAT(p.due_date, '%Y-%m')) DESC, p.id DESC");
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
?>
