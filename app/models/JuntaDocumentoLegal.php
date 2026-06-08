<?php
class JuntaDocumentoLegal extends Model {

    public function hasTable(): bool {
        try {
            $this->db->query('SELECT 1 FROM junta_documentos_legales LIMIT 1');
            $this->db->execute();
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function getByJunta(int $juntaId): array {
        if (!$this->hasTable()) {
            return [];
        }
        $this->db->query('SELECT d.*, u.nombre AS subidor_nombre, u.apellido_paterno AS subidor_apellido
            FROM junta_documentos_legales d
            LEFT JOIN usuarios u ON u.id = d.subido_por
            WHERE d.junta_id = :junta_id
            ORDER BY d.created_at DESC');
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    public function getById(int $id, int $juntaId) {
        if (!$this->hasTable()) {
            return null;
        }
        $this->db->query('SELECT * FROM junta_documentos_legales WHERE id = :id AND junta_id = :junta_id LIMIT 1');
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->single();
    }

    public function create(int $juntaId, int $subidoPor, array $fileMeta, string $titulo = 'Documento legal'): ?int {
        if (!$this->hasTable()) {
            return null;
        }
        $this->db->query('INSERT INTO junta_documentos_legales
            (junta_id, titulo, archivo_path, archivo_nombre_original, mime_type, tamano_bytes, subido_por)
            VALUES (:junta_id, :titulo, :archivo_path, :archivo_nombre_original, :mime_type, :tamano_bytes, :subido_por)');
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':titulo', $titulo !== '' ? $titulo : 'Documento legal');
        $this->db->bind(':archivo_path', $fileMeta['path']);
        $this->db->bind(':archivo_nombre_original', $fileMeta['archivo_nombre_original']);
        $this->db->bind(':mime_type', $fileMeta['mime_type']);
        $this->db->bind(':tamano_bytes', $fileMeta['tamano_bytes']);
        $this->db->bind(':subido_por', $subidoPor);
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
        $this->db->query('DELETE FROM junta_documentos_legales WHERE id = :id AND junta_id = :junta_id');
        $this->db->bind(':id', $id);
        $this->db->bind(':junta_id', $juntaId);
        if (!$this->db->execute()) {
            return null;
        }
        return $doc->archivo_path;
    }
}
