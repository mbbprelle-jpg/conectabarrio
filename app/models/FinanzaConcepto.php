<?php
class FinanzaConcepto extends Model {

    private static ?bool $hasConceptosTable = null;

    public function hasConceptosTable(): bool {
        if (self::$hasConceptosTable === null) {
            try {
                $this->db->query('SELECT id FROM finanzas_conceptos LIMIT 1');
                $this->db->execute();
                self::$hasConceptosTable = true;
            } catch (Exception $e) {
                self::$hasConceptosTable = false;
            }
        }
        return self::$hasConceptosTable;
    }

    private const DEFAULTS_INGRESO = [
        'Donación',
        'Subsidio Municipal',
        'Evento',
        'Otros',
    ];

    private const DEFAULTS_EGRESO = [
        'Pago Luz',
        'Pago Agua',
        'Pago Gas',
        'Insumos Oficina',
        'Reparaciones',
        'Gastos de Oficina',
        'Otros',
    ];

    public function ensureDefaults(int $juntaId): void {
        if (!$this->hasConceptosTable()) {
            return;
        }
        if ($this->countByJunta($juntaId) > 0) {
            return;
        }
        $orden = 0;
        foreach (self::DEFAULTS_INGRESO as $nombre) {
            $this->insertConcepto($juntaId, 'ingreso', $nombre, $orden++);
        }
        $orden = 0;
        foreach (self::DEFAULTS_EGRESO as $nombre) {
            $this->insertConcepto($juntaId, 'egreso', $nombre, $orden++);
        }
    }

    private function insertConcepto(int $juntaId, string $tipo, string $nombre, int $orden): void {
        $this->db->query('INSERT INTO finanzas_conceptos (junta_id, tipo, nombre, activo, orden)
            VALUES (:junta_id, :tipo, :nombre, 1, :orden)');
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':tipo', $tipo);
        $this->db->bind(':nombre', $nombre);
        $this->db->bind(':orden', $orden);
        $this->db->execute();
    }

    public function countByJunta(int $juntaId): int {
        $this->db->query('SELECT COUNT(*) AS total FROM finanzas_conceptos WHERE junta_id = :junta_id');
        $this->db->bind(':junta_id', $juntaId);
        $row = $this->db->single();
        return $row ? (int)$row->total : 0;
    }

    public function getByJunta(int $juntaId, ?string $tipo = null, bool $soloActivos = false): array {
        $sql = 'SELECT * FROM finanzas_conceptos WHERE junta_id = :junta_id';
        if ($tipo !== null) {
            $sql .= ' AND tipo = :tipo';
        }
        if ($soloActivos) {
            $sql .= ' AND activo = 1';
        }
        $sql .= ' ORDER BY tipo ASC, orden ASC, nombre ASC';
        $this->db->query($sql);
        $this->db->bind(':junta_id', $juntaId);
        if ($tipo !== null) {
            $this->db->bind(':tipo', $tipo);
        }
        return $this->db->resultSet();
    }

    public function getById(int $id, int $juntaId) {
        $this->db->query('SELECT * FROM finanzas_conceptos WHERE id = :id AND junta_id = :junta_id LIMIT 1');
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->single();
    }

    public function createConcepto(int $juntaId, string $tipo, string $nombre): bool {
        $nombre = trim($nombre);
        if ($nombre === '' || !in_array($tipo, ['ingreso', 'egreso'], true)) {
            return false;
        }
        if ($this->existsNombre($juntaId, $tipo, $nombre)) {
            return false;
        }
        $orden = $this->nextOrden($juntaId, $tipo);
        $this->db->query('INSERT INTO finanzas_conceptos (junta_id, tipo, nombre, activo, orden)
            VALUES (:junta_id, :tipo, :nombre, 1, :orden)');
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':tipo', $tipo);
        $this->db->bind(':nombre', $nombre);
        $this->db->bind(':orden', $orden);
        return $this->db->execute();
    }

    public function updateConcepto(int $id, int $juntaId, string $nombre, bool $activo): bool {
        $concepto = $this->getById($id, $juntaId);
        if (!$concepto) {
            return false;
        }
        $nombre = trim($nombre);
        if ($nombre === '') {
            return false;
        }
        if ($this->existsNombre($juntaId, $concepto->tipo, $nombre, $id)) {
            return false;
        }
        $this->db->query('UPDATE finanzas_conceptos SET nombre = :nombre, activo = :activo WHERE id = :id AND junta_id = :junta_id');
        $this->db->bind(':nombre', $nombre);
        $this->db->bind(':activo', $activo ? 1 : 0);
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->execute();
    }

    public function deleteConcepto(int $id, int $juntaId): bool {
        $concepto = $this->getById($id, $juntaId);
        if (!$concepto) {
            return false;
        }
        if ($this->countUsoConcepto($juntaId, $concepto->tipo, $concepto->nombre) > 0) {
            return false;
        }
        $this->db->query('DELETE FROM finanzas_conceptos WHERE id = :id AND junta_id = :junta_id');
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->execute();
    }

    public function countUsoConcepto(int $juntaId, string $tipo, string $nombre): int {
        $this->db->query('SELECT COUNT(*) AS total FROM transacciones
            WHERE junta_id = :junta_id AND tipo = :tipo AND categoria = :categoria');
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':tipo', $tipo);
        $this->db->bind(':nombre', $nombre);
        $row = $this->db->single();
        return $row ? (int)$row->total : 0;
    }

    /** @return object[] */
    public function getByJuntaWithUso(int $juntaId, string $tipo): array {
        $items = $this->getByJunta($juntaId, $tipo, false);
        foreach ($items as $item) {
            $item->uso_count = $this->countUsoConcepto($juntaId, $item->tipo, $item->nombre);
            $item->puede_eliminar = ($item->uso_count === 0);
        }
        return $items;
    }

    public function isConceptoValido(int $juntaId, string $tipo, string $categoria): bool {
        $this->db->query('SELECT id FROM finanzas_conceptos
            WHERE junta_id = :junta_id AND tipo = :tipo AND nombre = :nombre AND activo = 1 LIMIT 1');
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':tipo', $tipo);
        $this->db->bind(':nombre', $categoria);
        return (bool)$this->db->single();
    }

    private function existsNombre(int $juntaId, string $tipo, string $nombre, ?int $excludeId = null): bool {
        $sql = 'SELECT id FROM finanzas_conceptos WHERE junta_id = :junta_id AND tipo = :tipo AND nombre = :nombre';
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
        }
        $sql .= ' LIMIT 1';
        $this->db->query($sql);
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':tipo', $tipo);
        $this->db->bind(':nombre', $nombre);
        if ($excludeId !== null) {
            $this->db->bind(':exclude_id', $excludeId);
        }
        return (bool)$this->db->single();
    }

    private function nextOrden(int $juntaId, string $tipo): int {
        $this->db->query('SELECT COALESCE(MAX(orden), -1) + 1 AS next_orden FROM finanzas_conceptos
            WHERE junta_id = :junta_id AND tipo = :tipo');
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':tipo', $tipo);
        $row = $this->db->single();
        return $row ? (int)$row->next_orden : 0;
    }
}
