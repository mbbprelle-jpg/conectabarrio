<?php
class Payment extends Model {
    // Obtener todos los pagos de una organización
    public function getAllByOrg($orgId) {
        $this->db->query("SELECT * FROM payments WHERE org_id = :org_id ORDER BY due_date DESC");
        $this->db->bind(':org_id', $orgId);
        return $this->db->resultSet();
    }

    // Obtener pagos vencidos de una organización
    public function getOverdueByOrg($orgId) {
        $this->db->query("SELECT * FROM payments WHERE org_id = :org_id AND status = 'overdue' ORDER BY due_date DESC");
        $this->db->bind(':org_id', $orgId);
        return $this->db->resultSet();
    }

    // Crear nuevo pago
    public function create($data) {
        $this->db->query("INSERT INTO payments (org_id, amount, due_date, status) VALUES (:org_id, :amount, :due_date, :status)");
        $this->db->bind(':org_id', $data['org_id']);
        $this->db->bind(':amount', $data['amount']);
        $this->db->bind(':due_date', $data['due_date']);
        $this->db->bind(':status', $data['status'] ?? 'pending');
        return $this->db->execute();
    }

    // Actualizar pago existente
    public function update($id, $data) {
        $fields = [];
        if (isset($data['amount'])) $fields[] = "amount = :amount";
        if (isset($data['due_date'])) $fields[] = "due_date = :due_date";
        if (isset($data['status'])) $fields[] = "status = :status";
        if (empty($fields)) return false;
        $sql = "UPDATE payments SET " . implode(', ', $fields) . " WHERE id = :id";
        $this->db->query($sql);
        if (isset($data['amount'])) $this->db->bind(':amount', $data['amount']);
        if (isset($data['due_date'])) $this->db->bind(':due_date', $data['due_date']);
        if (isset($data['status'])) $this->db->bind(':status', $data['status']);
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // Eliminar pago
    public function delete($id) {
        $this->db->query("DELETE FROM payments WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // Resumen de pagos (al día vs vencidos)
    public function summarizeByOrg($orgId) {
        $this->db->query("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count FROM payments WHERE org_id = :org_id");
        $this->db->bind(':org_id', $orgId);
        $res = $this->db->single();
        $paid = $res ? (int)$res->paid_count : 0;
        $total = $res ? (int)$res->total : 0;
        $overdue = $total - $paid;
        return ['paid' => $paid, 'overdue' => $overdue];
    }
}
?>
