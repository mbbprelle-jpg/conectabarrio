<?php
class Membresia extends Model {

    public function getActiveByUsuario($usuarioId) {
        $this->db->query("SELECT m.*, j.nombre AS junta_nombre, j.comuna, j.plan, j.precio_anual
            FROM usuario_membresias m
            INNER JOIN juntas_vecinos j ON j.id = m.junta_id
            WHERE m.usuario_id = :usuario_id AND m.estado = 1
            ORDER BY j.nombre ASC");
        $this->db->bind(':usuario_id', $usuarioId);
        return $this->db->resultSet();
    }

    public function getById($id) {
        $this->db->query("SELECT m.*, j.nombre AS junta_nombre, j.comuna, j.plan, j.precio_anual
            FROM usuario_membresias m
            INNER JOIN juntas_vecinos j ON j.id = m.junta_id
            WHERE m.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    public function ensureFromUsuario($user) {
        if (empty($user->junta_id) || !in_array($user->rol, ['admin', 'socio'], true)) {
            return false;
        }
        $this->db->query("SELECT id FROM usuario_membresias WHERE usuario_id = :usuario_id AND junta_id = :junta_id LIMIT 1");
        $this->db->bind(':usuario_id', $user->id);
        $this->db->bind(':junta_id', $user->junta_id);
        if ($this->db->single()) {
            return true;
        }
        $this->db->query("INSERT INTO usuario_membresias (usuario_id, junta_id, rol, id_socio, estado)
            VALUES (:usuario_id, :junta_id, :rol, :id_socio, :estado)");
        $this->db->bind(':usuario_id', $user->id);
        $this->db->bind(':junta_id', $user->junta_id);
        $this->db->bind(':rol', $user->rol);
        $this->db->bind(':id_socio', $user->id_socio ?? null);
        $this->db->bind(':estado', $user->estado ?? 1);
        return $this->db->execute();
    }

    public function upsert($usuarioId, $juntaId, $rol, $extra = []) {
        $existing = $this->getByUsuarioJunta($usuarioId, $juntaId);
        if ($existing) {
            $this->db->query("UPDATE usuario_membresias SET rol = :rol, estado = 1 WHERE id = :id");
            $this->db->bind(':rol', $rol);
            $this->db->bind(':id', $existing->id);
            return $this->db->execute() ? $existing->id : false;
        }
        $this->db->query("INSERT INTO usuario_membresias (usuario_id, junta_id, rol, id_socio, estado)
            VALUES (:usuario_id, :junta_id, :rol, :id_socio, 1)");
        $this->db->bind(':usuario_id', $usuarioId);
        $this->db->bind(':junta_id', $juntaId);
        $this->db->bind(':rol', $rol);
        $this->db->bind(':id_socio', $extra['id_socio'] ?? null);
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function getByUsuarioJunta($usuarioId, $juntaId) {
        $this->db->query("SELECT * FROM usuario_membresias WHERE usuario_id = :usuario_id AND junta_id = :junta_id LIMIT 1");
        $this->db->bind(':usuario_id', $usuarioId);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->single();
    }

    public function updateDelegacion($membresiaId, $data) {
        $this->db->query("UPDATE usuario_membresias SET
            cargo = :cargo,
            permiso_gestion_socios = :permiso_gestion_socios,
            permiso_registro_pagos = :permiso_registro_pagos,
            permiso_todos = :permiso_todos
            WHERE id = :id");
        $this->db->bind(':cargo', $data['cargo'] ?: null);
        $this->db->bind(':permiso_gestion_socios', !empty($data['permiso_gestion_socios']) ? 1 : 0);
        $this->db->bind(':permiso_registro_pagos', !empty($data['permiso_registro_pagos']) ? 1 : 0);
        $this->db->bind(':permiso_todos', !empty($data['permiso_todos']) ? 1 : 0);
        $this->db->bind(':id', $membresiaId);
        return $this->db->execute();
    }

    public function getEquipoByJunta($juntaId) {
        $this->db->query("SELECT u.id, u.nombre, u.apellido_paterno, u.email, u.rut, u.estado, u.rol AS usuario_rol,
            m.id AS membresia_id, m.rol, m.cargo, m.permiso_gestion_socios, m.permiso_registro_pagos, m.permiso_todos
            FROM usuario_membresias m
            INNER JOIN usuarios u ON u.id = m.usuario_id
            WHERE m.junta_id = :junta_id AND m.estado = 1 AND u.rol != 'maestro'
            ORDER BY FIELD(m.rol, 'admin', 'socio'), u.nombre ASC");
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    public function countActiveAdmins($juntaId) {
        $this->db->query("SELECT COUNT(*) AS total FROM usuario_membresias
            WHERE junta_id = :junta_id AND rol = 'admin' AND estado = 1");
        $this->db->bind(':junta_id', $juntaId);
        $row = $this->db->single();
        return $row ? (int)$row->total : 0;
    }

    public function isOnlyActiveAdmin($usuarioId, $juntaId) {
        $mem = $this->getByUsuarioJunta($usuarioId, $juntaId);
        if (!$mem || $mem->rol !== 'admin' || (int)$mem->estado !== 1) {
            return false;
        }
        return $this->countActiveAdmins($juntaId) <= 1;
    }

    public function deactivate($usuarioId, $juntaId) {
        $this->db->query("UPDATE usuario_membresias SET estado = 0
            WHERE usuario_id = :usuario_id AND junta_id = :junta_id");
        $this->db->bind(':usuario_id', $usuarioId);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->execute();
    }

    public function deactivateAllForUsuario($usuarioId) {
        $this->db->query("UPDATE usuario_membresias SET estado = 0 WHERE usuario_id = :usuario_id");
        $this->db->bind(':usuario_id', $usuarioId);
        return $this->db->execute();
    }
}
