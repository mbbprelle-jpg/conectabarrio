<?php
class Documento extends Model {

    public function getByJunta(int $juntaId, ?int $categoriaId = null): array {
        $sql = 'SELECT d.*, c.nombre AS categoria_nombre, c.visibilidad AS categoria_visibilidad,
            u.nombre AS subido_por_nombre
            FROM documentos d
            INNER JOIN documento_categorias c ON c.id = d.categoria_id
            INNER JOIN usuarios u ON u.id = d.subido_por
            WHERE d.junta_id = :junta_id AND c.activo = 1';
        if ($categoriaId !== null && $categoriaId > 0) {
            $sql .= ' AND d.categoria_id = :categoria_id';
        }
        $sql .= ' ORDER BY d.created_at DESC';
        $this->db->query($sql);
        $this->db->bind(':junta_id', $juntaId);
        if ($categoriaId !== null && $categoriaId > 0) {
            $this->db->bind(':categoria_id', $categoriaId);
        }
        return $this->db->resultSet();
    }

    public function getById(int $id, int $juntaId) {
        $this->db->query('SELECT d.*, c.nombre AS categoria_nombre, c.visibilidad AS categoria_visibilidad,
            u.nombre AS subido_por_nombre
            FROM documentos d
            INNER JOIN documento_categorias c ON c.id = d.categoria_id
            INNER JOIN usuarios u ON u.id = d.subido_por
            WHERE d.id = :id AND d.junta_id = :junta_id LIMIT 1');
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->single();
    }

    public function create(array $data): ?int {
        $this->db->query('INSERT INTO documentos
            (junta_id, categoria_id, titulo, archivo_nombre_original, archivo_path, mime_type, tamano_bytes, subido_por)
            VALUES (:junta_id, :categoria_id, :titulo, :archivo_nombre_original, :archivo_path, :mime_type, :tamano_bytes, :subido_por)');
        $this->db->bind(':junta_id', $data['junta_id']);
        $this->db->bind(':categoria_id', $data['categoria_id']);
        $this->db->bind(':titulo', $data['titulo']);
        $this->db->bind(':archivo_nombre_original', $data['archivo_nombre_original']);
        $this->db->bind(':archivo_path', $data['archivo_path']);
        $this->db->bind(':mime_type', $data['mime_type']);
        $this->db->bind(':tamano_bytes', $data['tamano_bytes']);
        $this->db->bind(':subido_por', $data['subido_por']);
        if (!$this->db->execute()) {
            return null;
        }
        return (int)$this->db->lastInsertId();
    }

    public function delete(int $id, int $juntaId): ?string {
        $doc = $this->getById($id, $juntaId);
        if (!$doc) {
            return null;
        }
        $this->db->query('DELETE FROM documentos WHERE id = :id AND junta_id = :junta_id');
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        if (!$this->db->execute()) {
            return null;
        }
        return $doc->archivo_path;
    }
}
