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
        $this->db->query("SELECT
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count
            FROM payments WHERE org_id = :org_id");
        $this->db->bind(':org_id', $orgId);
        $res = $this->db->single();
        return [
            'paid' => $res ? (int)$res->paid_count : 0,
            'overdue' => $res ? (int)$res->overdue_count : 0
        ];
    }

    // Todos los pagos con nombre de organización (vista Maestro)
    public function getAllWithOrg() {
        $this->db->query("SELECT p.*, j.nombre AS org_nombre
            FROM payments p
            INNER JOIN juntas_vecinos j ON j.id = p.org_id
            ORDER BY p.due_date DESC");
        return $this->db->resultSet();
    }

    public function getById($id) {
        $this->db->query("SELECT p.*, j.nombre AS org_nombre
            FROM payments p
            INNER JOIN juntas_vecinos j ON j.id = p.org_id
            WHERE p.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function summarizeGlobal() {
        $this->db->query("SELECT
            SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count,
            SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count
            FROM payments");
        $res = $this->db->single();
        return [
            'paid' => $res ? (int)$res->paid_count : 0,
            'overdue' => $res ? (int)$res->overdue_count : 0
        ];
    }
}
?>
