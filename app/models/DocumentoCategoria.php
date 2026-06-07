<?php
class DocumentoCategoria extends Model {

    private const DEFAULTS = [
        ['nombre' => 'Comprobantes de ingreso', 'visibilidad' => 'publico'],
        ['nombre' => 'Comprobantes de egreso', 'visibilidad' => 'directorio'],
        ['nombre' => 'Actas y reuniones', 'visibilidad' => 'directorio'],
        ['nombre' => 'Documentos generales', 'visibilidad' => 'publico'],
    ];

    public function ensureDefaults(int $juntaId): void {
        $this->db->query('SELECT COUNT(*) AS total FROM documento_categorias WHERE junta_id = :junta_id');
        $this->db->bind(':junta_id', $juntaId);
        $row = $this->db->single();
        if ($row && (int)$row->total > 0) {
            return;
        }
        $orden = 0;
        foreach (self::DEFAULTS as $def) {
            $this->create($juntaId, $def['nombre'], $def['visibilidad'], $orden++);
        }
    }

    public function getByJunta(int $juntaId, bool $soloActivos = false): array {
        $sql = 'SELECT c.*, (SELECT COUNT(*) FROM documentos d WHERE d.categoria_id = c.id) AS num_documentos
            FROM documento_categorias c WHERE c.junta_id = :junta_id';
        if ($soloActivos) {
            $sql .= ' AND c.activo = 1';
        }
        $sql .= ' ORDER BY c.orden ASC, c.nombre ASC';
        $this->db->query($sql);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    public function getById(int $id, int $juntaId) {
        $this->db->query('SELECT * FROM documento_categorias WHERE id = :id AND junta_id = :junta_id LIMIT 1');
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->single();
    }

    public function create(int $juntaId, string $nombre, string $visibilidad, ?int $orden = null): bool {
        $nombre = trim($nombre);
        if ($nombre === '' || !in_array($visibilidad, ['publico', 'directorio'], true)) {
            return false;
        }
        if ($this->existsNombre($juntaId, $nombre)) {
            return false;
        }
        if ($orden === null) {
            $orden = $this->nextOrden($juntaId);
        }
        $this->db->query('INSERT INTO documento_categorias (junta_id, nombre, visibilidad, activo, orden)
            VALUES (:junta_id, :nombre, :visibilidad, 1, :orden)');
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':nombre', $nombre);
        $this->db->bind(':visibilidad', $visibilidad);
        $this->db->bind(':orden', $orden);
        return $this->db->execute();
    }

    public function update(int $id, int $juntaId, string $nombre, string $visibilidad, bool $activo): bool {
        $cat = $this->getById($id, $juntaId);
        if (!$cat) {
            return false;
        }
        $nombre = trim($nombre);
        if ($nombre === '' || !in_array($visibilidad, ['publico', 'directorio'], true)) {
            return false;
        }
        if ($this->existsNombre($juntaId, $nombre, $id)) {
            return false;
        }
        $this->db->query('UPDATE documento_categorias SET nombre = :nombre, visibilidad = :visibilidad, activo = :activo
            WHERE id = :id AND junta_id = :junta_id');
        $this->db->bind(':nombre', $nombre);
        $this->db->bind(':visibilidad', $visibilidad);
        $this->db->bind(':activo', $activo ? 1 : 0);
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->execute();
    }

    public function deleteOrDeactivate(int $id, int $juntaId): string {
        $cat = $this->getById($id, $juntaId);
        if (!$cat) {
            return 'missing';
        }
        $this->db->query('SELECT COUNT(*) AS total FROM documentos WHERE categoria_id = :id');
        $this->db->bind(':id', $id);
        $row = $this->db->single();
        if ($row && (int)$row->total > 0) {
            $this->db->query('UPDATE documento_categorias SET activo = 0 WHERE id = :id AND junta_id = :junta_id');
            $this->db->bind(':id', $id);
            $this->db->bind(':junta_id', $juntaId);
            return $this->db->execute() ? 'deactivated' : 'error';
        }
        $this->db->query('DELETE FROM documento_categorias WHERE id = :id AND junta_id = :junta_id');
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->execute() ? 'deleted' : 'error';
    }

    public function countDocumentos(int $categoriaId): int {
        $this->db->query('SELECT COUNT(*) AS total FROM documentos WHERE categoria_id = :id');
        $this->db->bind(':id', $categoriaId);
        $row = $this->db->single();
        return $row ? (int)$row->total : 0;
    }

    private function existsNombre(int $juntaId, string $nombre, ?int $excludeId = null): bool {
        $sql = 'SELECT id FROM documento_categorias WHERE junta_id = :junta_id AND nombre = :nombre';
        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
        }
        $sql .= ' LIMIT 1';
        $this->db->query($sql);
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':nombre', $nombre);
        if ($excludeId !== null) {
            $this->db->bind(':exclude_id', $excludeId);
        }
        return (bool)$this->db->single();
    }

    private function nextOrden(int $juntaId): int {
        $this->db->query('SELECT COALESCE(MAX(orden), -1) + 1 AS next_ord FROM documento_categorias WHERE junta_id = :junta_id');
        $this->db->bind(':junta_id', $juntaId);
        $row = $this->db->single();
        return $row ? (int)$row->next_ord : 0;
    }
}
