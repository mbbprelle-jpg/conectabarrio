<?php
class Membresia extends Model {

    private static $hasDomicilioColumn = null;

    public function hasDomicilioColumn(): bool {
        if (self::$hasDomicilioColumn === null) {
            try {
                $this->db->query('SELECT calle_id FROM usuario_membresias LIMIT 1');
                $this->db->execute();
                self::$hasDomicilioColumn = true;
            } catch (Exception $e) {
                self::$hasDomicilioColumn = false;
            }
        }
        return self::$hasDomicilioColumn;
    }

    private function juntaSelectFields(): string {
        $fields = 'j.nombre AS junta_nombre, j.comuna, j.plan, j.precio_anual, j.tipo AS junta_tipo, j.lat_sede, j.lng_sede';
        if ($this->hasMapaSociosJuntaColumn()) {
            $fields .= ', j.mapa_socios_habilitado';
        }
        if ($this->hasFlujoCajaJuntaColumn()) {
            $fields .= ', j.flujo_caja_habilitado';
        }
        if ($this->hasDocumentosJuntaColumn()) {
            $fields .= ', j.documentos_habilitado';
        }
        return $fields;
    }

    private static $hasFlujoCajaJuntaColumn = null;

    public function hasFlujoCajaJuntaColumn(): bool {
        if (self::$hasFlujoCajaJuntaColumn === null) {
            try {
                $this->db->query('SELECT flujo_caja_habilitado FROM juntas_vecinos LIMIT 1');
                $this->db->execute();
                self::$hasFlujoCajaJuntaColumn = true;
            } catch (Exception $e) {
                self::$hasFlujoCajaJuntaColumn = false;
            }
        }
        return self::$hasFlujoCajaJuntaColumn;
    }

    private static $hasMapaSociosJuntaColumn = null;

    public function hasMapaSociosJuntaColumn(): bool {
        if (self::$hasMapaSociosJuntaColumn === null) {
            try {
                $this->db->query('SELECT mapa_socios_habilitado FROM juntas_vecinos LIMIT 1');
                $this->db->execute();
                self::$hasMapaSociosJuntaColumn = true;
            } catch (Exception $e) {
                self::$hasMapaSociosJuntaColumn = false;
            }
        }
        return self::$hasMapaSociosJuntaColumn;
    }

    private static $hasPermisoMapaColumn = null;

    public function hasPermisoMapaColumn(): bool {
        if (self::$hasPermisoMapaColumn === null) {
            try {
                $this->db->query('SELECT permiso_mapa_socios FROM usuario_membresias LIMIT 1');
                $this->db->execute();
                self::$hasPermisoMapaColumn = true;
            } catch (Exception $e) {
                self::$hasPermisoMapaColumn = false;
            }
        }
        return self::$hasPermisoMapaColumn;
    }

    private static $hasPermisoFlujoColumn = null;

    public function hasPermisoFlujoColumn(): bool {
        if (self::$hasPermisoFlujoColumn === null) {
            try {
                $this->db->query('SELECT permiso_flujo_caja FROM usuario_membresias LIMIT 1');
                $this->db->execute();
                self::$hasPermisoFlujoColumn = true;
            } catch (Exception $e) {
                self::$hasPermisoFlujoColumn = false;
            }
        }
        return self::$hasPermisoFlujoColumn;
    }

    private static $hasPermisoDocumentosColumn = null;

    public function hasPermisoDocumentosColumn(): bool {
        if (self::$hasPermisoDocumentosColumn === null) {
            try {
                $this->db->query('SELECT permiso_documentos FROM usuario_membresias LIMIT 1');
                $this->db->execute();
                self::$hasPermisoDocumentosColumn = true;
            } catch (Exception $e) {
                self::$hasPermisoDocumentosColumn = false;
            }
        }
        return self::$hasPermisoDocumentosColumn;
    }

    private static $hasDocumentosJuntaColumn = null;

    public function hasDocumentosJuntaColumn(): bool {
        if (self::$hasDocumentosJuntaColumn === null) {
            try {
                $this->db->query('SELECT documentos_habilitado FROM juntas_vecinos LIMIT 1');
                $this->db->execute();
                self::$hasDocumentosJuntaColumn = true;
            } catch (Exception $e) {
                self::$hasDocumentosJuntaColumn = false;
            }
        }
        return self::$hasDocumentosJuntaColumn;
    }

    public function getActiveByUsuario($usuarioId) {
        $this->db->query("SELECT m.*, {$this->juntaSelectFields()}
            FROM usuario_membresias m
            INNER JOIN juntas_vecinos j ON j.id = m.junta_id
            WHERE m.usuario_id = :usuario_id AND m.estado = 1
            ORDER BY j.nombre ASC");
        $this->db->bind(':usuario_id', $usuarioId);
        return $this->db->resultSet();
    }

    public function getById($id) {
        $this->db->query("SELECT m.*, {$this->juntaSelectFields()}
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
        $this->db->query("SELECT m.*, c.nombre AS calle_nombre
            FROM usuario_membresias m
            LEFT JOIN calles c ON c.id = m.calle_id
            WHERE m.usuario_id = :usuario_id AND m.junta_id = :junta_id LIMIT 1");
        $this->db->bind(':usuario_id', $usuarioId);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->single();
    }

    public function updateDomicilio(int $usuarioId, int $juntaId, array $data): bool {
        if (!$this->hasDomicilioColumn()) {
            return false;
        }
        $mem = $this->getByUsuarioJunta($usuarioId, $juntaId);
        if (!$mem) {
            return false;
        }
        $this->db->query("UPDATE usuario_membresias SET
            calle_id = :calle_id,
            numero_casa = :numero_casa,
            direccion_texto = :direccion_texto,
            latitud = :latitud,
            longitud = :longitud,
            link_google = :link_google
            WHERE id = :id");
        $this->db->bind(':calle_id', !empty($data['calle_id']) ? (int)$data['calle_id'] : null);
        $this->db->bind(':numero_casa', !empty($data['numero_casa']) ? $data['numero_casa'] : null);
        $this->db->bind(':direccion_texto', !empty($data['direccion_texto']) ? $data['direccion_texto'] : null);
        $this->db->bind(':latitud', isset($data['latitud']) && $data['latitud'] !== '' ? $data['latitud'] : null);
        $this->db->bind(':longitud', isset($data['longitud']) && $data['longitud'] !== '' ? $data['longitud'] : null);
        $this->db->bind(':link_google', !empty($data['link_google']) ? $data['link_google'] : null);
        $this->db->bind(':id', $mem->id);
        return $this->db->execute();
    }

    public function overlayDomicilioOnUser($user, int $juntaId) {
        if (!$user) {
            return $user;
        }
        $mem = $this->getByUsuarioJunta((int)$user->id, $juntaId);
        if (!$mem) {
            return $user;
        }
        if ($mem->id_socio !== null && $mem->id_socio !== '') {
            $user->id_socio = (int)$mem->id_socio;
        }
        if (!$this->hasDomicilioColumn()) {
            return $user;
        }
        if ($mem->calle_id !== null) {
            $user->calle_id = $mem->calle_id;
        }
        if ($mem->numero_casa !== null) {
            $user->numero_casa = $mem->numero_casa;
        }
        if (!empty($mem->direccion_texto)) {
            $user->direccion_texto = $mem->direccion_texto;
        }
        if ($mem->latitud !== null) {
            $user->latitud = $mem->latitud;
        }
        if ($mem->longitud !== null) {
            $user->longitud = $mem->longitud;
        }
        if (!empty($mem->link_google)) {
            $user->link_google = $mem->link_google;
        }
        if (!empty($mem->calle_nombre)) {
            $user->calle_nombre = $mem->calle_nombre;
        }
        return $user;
    }

    public function overlayDomicilioOnUsers(array $users, int $juntaId): array {
        foreach ($users as $idx => $user) {
            $users[$idx] = $this->overlayDomicilioOnUser($user, $juntaId);
        }
        return $users;
    }

    public function updateDelegacion($membresiaId, $data) {
        $sql = "UPDATE usuario_membresias SET
            cargo = :cargo,
            permiso_gestion_socios = :permiso_gestion_socios,
            permiso_registro_pagos = :permiso_registro_pagos,
            permiso_todos = :permiso_todos";
        if ($this->hasPermisoMapaColumn()) {
            $sql .= ', permiso_mapa_socios = :permiso_mapa_socios';
        }
        if ($this->hasPermisoFlujoColumn()) {
            $sql .= ', permiso_flujo_caja = :permiso_flujo_caja';
        }
        if ($this->hasPermisoDocumentosColumn()) {
            $sql .= ', permiso_documentos = :permiso_documentos';
        }
        $sql .= ' WHERE id = :id';
        $this->db->query($sql);
        $this->db->bind(':cargo', $data['cargo'] ?: null);
        $this->db->bind(':permiso_gestion_socios', !empty($data['permiso_gestion_socios']) ? 1 : 0);
        $this->db->bind(':permiso_registro_pagos', !empty($data['permiso_registro_pagos']) ? 1 : 0);
        $this->db->bind(':permiso_todos', !empty($data['permiso_todos']) ? 1 : 0);
        if ($this->hasPermisoMapaColumn()) {
            $this->db->bind(':permiso_mapa_socios', !empty($data['permiso_mapa_socios']) ? 1 : 0);
        }
        if ($this->hasPermisoFlujoColumn()) {
            $this->db->bind(':permiso_flujo_caja', !empty($data['permiso_flujo_caja']) ? 1 : 0);
        }
        if ($this->hasPermisoDocumentosColumn()) {
            $this->db->bind(':permiso_documentos', !empty($data['permiso_documentos']) ? 1 : 0);
        }
        $this->db->bind(':id', $membresiaId);
        return $this->db->execute();
    }

    /** Miembros del padrón (socios + administradores) con coordenadas para el mapa. */
    public function buildMapaSociosDataset(int $juntaId, array $miembros): array {
        $total = count($miembros);
        $puntos = [];
        $geolocalizados = 0;

        foreach ($miembros as $miembro) {
            $miembro = $this->overlayDomicilioOnUser($miembro, $juntaId);
            $lat = $miembro->latitud ?? null;
            $lng = $miembro->longitud ?? null;
            if ($lat === null || $lng === null || $lat === '' || $lng === '' || !is_numeric($lat) || !is_numeric($lng)) {
                continue;
            }
            $lat = (float)$lat;
            $lng = (float)$lng;
            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                continue;
            }
            $geolocalizados++;
            $nombre = trim(($miembro->nombre ?? '') . ' ' . ($miembro->apellido_paterno ?? ''));
            $esAdmin = ($miembro->rol ?? '') === 'admin';
            $rolLabel = $esAdmin ? 'Administrador' : 'Socio';
            $puntos[] = [
                'lat' => $lat,
                'lng' => $lng,
                'id_socio' => $miembro->id_socio ?? null,
                'rol' => $rolLabel,
                'label' => $nombre !== '' ? $nombre : ('Miembro #' . ($miembro->id_socio ?? $miembro->id)),
            ];
        }

        return [
            'total' => $total,
            'geolocalizados' => $geolocalizados,
            'sin_geolocalizar' => max(0, $total - $geolocalizados),
            'puntos' => $puntos,
        ];
    }

    public function getEquipoByJunta($juntaId) {
        $this->db->query("SELECT u.id, u.nombre, u.apellido_paterno, u.email, u.rut, u.estado, u.rol AS usuario_rol,
            m.id AS membresia_id, m.rol, m.cargo, m.permiso_gestion_socios, m.permiso_registro_pagos, m.permiso_todos"
            . ($this->hasPermisoMapaColumn() ? ', m.permiso_mapa_socios' : '')
            . ($this->hasPermisoFlujoColumn() ? ', m.permiso_flujo_caja' : '')
            . ($this->hasPermisoDocumentosColumn() ? ', m.permiso_documentos' : '')
            . " FROM usuario_membresias m
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
