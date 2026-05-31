<?php
class User extends Model {
    private static $hasCalleIdColumn = null;
    private static $hasStatusColumn = null;
    private static $hasGeneroColumn = null;

    private function hasCalleIdColumn() {
        if (self::$hasCalleIdColumn === null) {
            try {
                $this->db->query('SELECT calle_id FROM usuarios LIMIT 1');
                $this->db->execute();
                self::$hasCalleIdColumn = true;
            } catch (Exception $e) {
                self::$hasCalleIdColumn = false;
            }
        }
        return self::$hasCalleIdColumn;
    }

    private function hasStatusColumn() {
        if (self::$hasStatusColumn === null) {
            try {
                $this->db->query('SELECT status FROM usuarios LIMIT 1');
                $this->db->execute();
                self::$hasStatusColumn = true;
            } catch (Exception $e) {
                self::$hasStatusColumn = false;
            }
        }
        return self::$hasStatusColumn;
    }

    private function hasGeneroColumn() {
        if (self::$hasGeneroColumn === null) {
            try {
                $this->db->query('SELECT genero FROM usuarios LIMIT 1');
                $this->db->execute();
                self::$hasGeneroColumn = true;
            } catch (Exception $e) {
                self::$hasGeneroColumn = false;
            }
        }
        return self::$hasGeneroColumn;
    }
    
    // Buscar usuario por correo electrónico
    public function findUserByEmail($email) {
        $this->db->query("SELECT * FROM usuarios WHERE email = :email");
        $this->db->bind(':email', $email);
        return $this->db->single();
    }

    // Buscar usuario por RUT (ID Legal de Chile)
    public function findUserByRut($rut) {
        $this->db->query("SELECT * FROM usuarios WHERE rut = :rut");
        $this->db->bind(':rut', $rut);
        return $this->db->single();
    }

    // Iniciar Sesión (Validar RUT/Email y Contraseña)
    public function login($rutOrEmail, $password) {
        // Permitir ingresar con RUT o Email
        if (filter_var($rutOrEmail, FILTER_VALIDATE_EMAIL)) {
            $row = $this->findUserByEmail($rutOrEmail);
        } else {
            $row = $this->findUserByRut($rutOrEmail);
        }

        if (!$row) {
            return false; // Usuario no existe
        }

        // Verificar contraseña cifrada
        if (password_verify($password, $row->password)) {
            if ($this->hasStatusColumn() && ($row->status ?? 'active') === 'pending') {
                return false;
            }
            // Verificar si el usuario está activo
            if ($row->estado == 1) {
                return $row;
            }
        }
        
        return false;
    }

    // Crear un nuevo Socio o Administrador
    public function register($data) {
        $this->db->query("INSERT INTO usuarios (junta_id, id_socio, rut, nombre, apellido_paterno, apellido_materno, email, password, rol, telefono, estado, calle_id, numero_casa, fecha_inicio) VALUES (:junta_id, :id_socio, :rut, :nombre, :apellido_paterno, :apellido_materno, :email, :password, :rol, :telefono, :estado, :calle_id, :numero_casa, :fecha_inicio)");
        
        // Cifrar la contraseña
        $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);
        
        // Vincular parámetros
        $this->db->bind(':junta_id', $data['junta_id']);
        $this->db->bind(':id_socio', isset($data['id_socio']) && !empty($data['id_socio']) ? $data['id_socio'] : null);
        $this->db->bind(':rut', $data['rut']);
        $this->db->bind(':nombre', $data['nombres']);
        $this->db->bind(':apellido_paterno', $data['apellido_paterno']);
        $this->db->bind(':apellido_materno', $data['apellido_materno']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password', $hashed_password);
        $this->db->bind(':rol', $data['rol']);
        $this->db->bind(':telefono', $data['telefono']);
        $this->db->bind(':estado', isset($data['estado']) ? $data['estado'] : 1);
        $this->db->bind(':calle_id', isset($data['calle_id']) && !empty($data['calle_id']) ? $data['calle_id'] : null);
        $this->db->bind(':numero_casa', isset($data['numero_casa']) && !empty($data['numero_casa']) ? $data['numero_casa'] : null);
        $this->db->bind(':fecha_inicio', isset($data['fecha_inicio']) && !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : date('Y-m-d'));

        // Ejecutar
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    // Actualizar datos del Socio
    public function update($data) {
        $this->db->query("UPDATE usuarios SET rut = :rut, nombre = :nombre, apellido_paterno = :apellido_paterno, apellido_materno = :apellido_materno, email = :email, telefono = :telefono, calle_id = :calle_id, numero_casa = :numero_casa WHERE id = :id");
        $this->db->bind(':rut', $data['rut']);
        $this->db->bind(':nombre', $data['nombres']);
        $this->db->bind(':apellido_paterno', $data['apellido_paterno']);
        $this->db->bind(':apellido_materno', $data['apellido_materno']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':telefono', $data['telefono']);
        $this->db->bind(':calle_id', isset($data['calle_id']) && !empty($data['calle_id']) ? $data['calle_id'] : null);
        $this->db->bind(':numero_casa', isset($data['numero_casa']) && !empty($data['numero_casa']) ? $data['numero_casa'] : null);
        $this->db->bind(':id', $data['id']);

        return $this->db->execute();
    }

    public function updateSocio($data) {
        $this->db->query("UPDATE usuarios SET
            id_socio = :id_socio,
            rut = :rut,
            nombre = :nombre,
            apellido_paterno = :apellido_paterno,
            apellido_materno = :apellido_materno,
            email = :email,
            telefono = :telefono,
            calle_id = :calle_id,
            numero_casa = :numero_casa,
            fecha_inicio = :fecha_inicio
            WHERE id = :id AND rol = 'socio'");
        $this->db->bind(':id_socio', !empty($data['id_socio']) ? (int)$data['id_socio'] : null);
        $this->db->bind(':rut', $data['rut']);
        $this->db->bind(':nombre', $data['nombres']);
        $this->db->bind(':apellido_paterno', $data['apellido_paterno']);
        $this->db->bind(':apellido_materno', $data['apellido_materno']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':telefono', $data['telefono'] ?? '');
        $this->db->bind(':calle_id', !empty($data['calle_id']) ? $data['calle_id'] : null);
        $this->db->bind(':numero_casa', !empty($data['numero_casa']) ? $data['numero_casa'] : null);
        $this->db->bind(':fecha_inicio', !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : date('Y-m-d'));
        $this->db->bind(':id', $data['id']);
        return $this->db->execute();
    }

    // Establecer contraseña temporal y marcar para cambio obligatorio
    public function setTempPassword($userId, $hash) {
        $this->db->query("UPDATE usuarios SET password = :pwd, must_change = 1 WHERE id = :id");
        $this->db->bind(':pwd', $hash);
        $this->db->bind(':id', $userId);
        return $this->db->execute();
    }

    // Asignar o revocar rol admin
    public function setAdmin($userId, $isAdmin) {
        $role = $isAdmin ? 'admin' : 'socio';
        $this->db->query("UPDATE usuarios SET rol = :rol WHERE id = :id");
        $this->db->bind(':rol', $role);
        $this->db->bind(':id', $userId);
        return $this->db->execute();
    }

    // Cambiar contraseña definitiva y quitar flag de cambio obligatorio
    public function resetPassword($userId, $newPassword) {
        $this->db->query("UPDATE usuarios SET password = :pwd, must_change = 0 WHERE id = :id");
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->db->bind(':pwd', $hashed);
        $this->db->bind(':id', $userId);
        return $this->db->execute();
    }

    public function changePasswordWithCurrent($userId, $currentPassword, $newPassword) {
        $user = $this->getUserById($userId);
        if (!$user || !password_verify($currentPassword, $user->password)) {
            return ['ok' => false, 'error' => 'La contraseña actual no es correcta.'];
        }
        if (password_verify($newPassword, $user->password)) {
            return ['ok' => false, 'error' => 'La nueva contraseña debe ser distinta a la actual.'];
        }
        if ($this->resetPassword($userId, $newPassword)) {
            return ['ok' => true];
        }
        return ['ok' => false, 'error' => 'No se pudo guardar la nueva contraseña.'];
    }


    // Obtener administradores activos de una organización
    public function getAdminsByJunta($juntaId) {
        $this->db->query("SELECT DISTINCT u.* FROM usuarios u
            INNER JOIN usuario_membresias m ON m.usuario_id = u.id
            WHERE m.junta_id = :junta_id AND m.rol = 'admin' AND m.estado = 1 AND u.estado = 1
            ORDER BY u.nombre ASC");
        $this->db->bind(':junta_id', $juntaId);
        $admins = $this->db->resultSet();
        if (!empty($admins)) {
            return $admins;
        }
        $this->db->query("SELECT * FROM usuarios WHERE junta_id = :junta_id AND rol = 'admin' AND estado = 1 ORDER BY nombre ASC");
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    // Obtener socios asociados a una Junta de Vecinos
    public function getSociosByJunta($juntaId) {
        if ($this->hasStatusColumn()) {
            $this->db->query("SELECT * FROM usuarios WHERE junta_id = :junta_id AND rol = 'socio' AND estado = 1 AND status = 'active' ORDER BY nombre ASC");
        } else {
            $this->db->query("SELECT * FROM usuarios WHERE junta_id = :junta_id AND rol = 'socio' AND estado = 1 ORDER BY nombre ASC");
        }
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    public function getPendingByJunta($juntaId) {
        if (!$this->hasStatusColumn()) {
            return [];
        }
        if ($this->hasCalleIdColumn()) {
            $this->db->query("SELECT u.*, c.nombre AS calle_nombre FROM usuarios u
                LEFT JOIN calles c ON u.calle_id = c.id
                WHERE u.junta_id = :junta_id AND u.rol = 'socio' AND u.status = 'pending'
                ORDER BY u.created_at DESC");
        } else {
            $this->db->query("SELECT u.* FROM usuarios u
                WHERE u.junta_id = :junta_id AND u.rol = 'socio' AND u.status = 'pending'
                ORDER BY u.created_at DESC");
        }
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    public function getPendingById($userId, $juntaId) {
        if (!$this->hasStatusColumn()) {
            return null;
        }
        if ($this->hasCalleIdColumn()) {
            $this->db->query("SELECT u.*, c.nombre AS calle_nombre FROM usuarios u
                LEFT JOIN calles c ON u.calle_id = c.id
                WHERE u.id = :id AND u.junta_id = :junta_id AND u.rol = 'socio' AND u.status = 'pending'");
        } else {
            $this->db->query("SELECT u.* FROM usuarios u
                WHERE u.id = :id AND u.junta_id = :junta_id AND u.rol = 'socio' AND u.status = 'pending'");
        }
        $this->db->bind(':id', $userId);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->single();
    }

    public function updatePending($data) {
        if (!$this->hasStatusColumn()) {
            return false;
        }
        $extra = '';
        if ($this->hasGeneroColumn()) {
            $extra = ', genero = :genero, fecha_nacimiento = :fecha_nacimiento';
        }
        $this->db->query("UPDATE usuarios SET
            id_socio = :id_socio,
            rut = :rut,
            nombre = :nombre,
            apellido_paterno = :apellido_paterno,
            apellido_materno = :apellido_materno,
            email = :email,
            telefono = :telefono,
            calle_id = :calle_id,
            numero_casa = :numero_casa,
            fecha_inicio = :fecha_inicio
            {$extra}
            WHERE id = :id AND rol = 'socio' AND status = 'pending'");
        $this->db->bind(':id_socio', !empty($data['id_socio']) ? (int)$data['id_socio'] : null);
        $this->db->bind(':rut', $data['rut']);
        $this->db->bind(':nombre', $data['nombres']);
        $this->db->bind(':apellido_paterno', $data['apellido_paterno']);
        $this->db->bind(':apellido_materno', $data['apellido_materno']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':telefono', $data['telefono'] ?? '');
        $this->db->bind(':calle_id', !empty($data['calle_id']) ? $data['calle_id'] : null);
        $this->db->bind(':numero_casa', !empty($data['numero_casa']) ? $data['numero_casa'] : null);
        $this->db->bind(':fecha_inicio', !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : date('Y-m-d'));
        if ($this->hasGeneroColumn()) {
            $this->db->bind(':genero', $data['genero'] ?? null);
            $this->db->bind(':fecha_nacimiento', !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null);
        }
        $this->db->bind(':id', $data['id']);
        return $this->db->execute();
    }

    // Obtener un socio específico por su ID
    public function getSocioById($socioId) {
        if ($this->hasCalleIdColumn()) {
            $this->db->query("SELECT u.*, c.nombre AS calle_nombre, j.nombre AS junta_nombre
                FROM usuarios u
                LEFT JOIN calles c ON u.calle_id = c.id
                LEFT JOIN juntas_vecinos j ON u.junta_id = j.id
                WHERE u.id = :id AND u.rol = 'socio'");
        } else {
            $this->db->query("SELECT u.*, j.nombre AS junta_nombre
                FROM usuarios u
                LEFT JOIN juntas_vecinos j ON u.junta_id = j.id
                WHERE u.id = :id AND u.rol = 'socio'");
        }
        $this->db->bind(':id', $socioId);
        return $this->db->single();
    }

    // Obtener perfil completo por ID (Cualquier rol)
    public function getUserById($id) {
        $this->db->query("SELECT u.*, j.nombre as junta_nombre FROM usuarios u LEFT JOIN juntas_vecinos j ON u.junta_id = j.id WHERE u.id = :id");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    // Eliminar o desactivar usuario
    // Eliminar o desactivar usuario (Baja Lógica)
    public function delete($id) {
        $this->db->query("UPDATE usuarios SET estado = 0 WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // Reactivar un socio dado de baja
    public function reactivate($id) {
        $this->db->query("UPDATE usuarios SET estado = 1 WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    // Obtener socios inactivos (de baja) de una Junta
    public function getSociosInactivosByJunta($juntaId) {
        $this->db->query("SELECT * FROM usuarios WHERE junta_id = :junta_id AND rol = 'socio' AND estado = 0 ORDER BY nombre ASC");
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->resultSet();
    }

    // Obtener la cantidad de socios activos en una Junta de Vecinos
    public function getSociosCountByJunta($juntaId) {
        if ($this->hasStatusColumn()) {
            $this->db->query("SELECT COUNT(*) as total FROM usuarios WHERE junta_id = :junta_id AND rol = 'socio' AND estado = 1 AND status = 'active'");
        } else {
            $this->db->query("SELECT COUNT(*) as total FROM usuarios WHERE junta_id = :junta_id AND rol = 'socio' AND estado = 1");
        }
        $this->db->bind(':junta_id', $juntaId);
        $res = $this->db->single();
        return $res ? (int)$res->total : 0;
    }
    /**
    * Generate a random password (12 characters) for temporary use.
    */
    public function generateRandomPassword(int $length = 12): string {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%&*?';
        $pwd = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $pwd .= $chars[random_int(0, $max)];
        }
        return $pwd;
    }

    /**
     * Create a pending user entry linked to an invitation.
     */
    public function createPending(array $data, int $invitationId) {
        if (!$this->hasStatusColumn()) {
            throw new Exception('La columna status no existe. Ejecute sql/create_invitations_and_user_updates.sql');
        }
        $cols = 'junta_id, id_socio, rut, nombre, apellido_paterno, apellido_materno, email, password, rol, telefono, estado, calle_id, numero_casa, fecha_inicio, status, invitation_id';
        $vals = ':junta_id, :id_socio, :rut, :nombre, :apellido_paterno, :apellido_materno, :email, :password, :rol, :telefono, :estado, :calle_id, :numero_casa, :fecha_inicio, \'pending\', :invitation_id';
        if ($this->hasGeneroColumn()) {
            $cols .= ', genero, fecha_nacimiento';
            $vals .= ', :genero, :fecha_nacimiento';
        }
        $this->db->query("INSERT INTO usuarios ({$cols}) VALUES ({$vals})");
        $hashed = password_hash($data['password'], PASSWORD_BCRYPT);
        $this->db->bind(':junta_id', $data['junta_id']);
        $this->db->bind(':id_socio', !empty($data['id_socio']) ? (int)$data['id_socio'] : null);
        $this->db->bind(':rut', $data['rut']);
        $this->db->bind(':nombre', $data['nombres']);
        $this->db->bind(':apellido_paterno', $data['apellido_paterno']);
        $this->db->bind(':apellido_materno', $data['apellido_materno']);
        $this->db->bind(':email', $data['email']);
        $this->db->bind(':password', $hashed);
        $this->db->bind(':rol', $data['rol']);
        $this->db->bind(':telefono', $data['telefono']);
        $this->db->bind(':estado', $data['estado'] ?? 1);
        $this->db->bind(':calle_id', $data['calle_id'] ?? null);
        $this->db->bind(':numero_casa', $data['numero_casa'] ?? null);
        $this->db->bind(':fecha_inicio', $data['fecha_inicio'] ?? date('Y-m-d'));
        $this->db->bind(':invitation_id', $invitationId);
        if ($this->hasGeneroColumn()) {
            $this->db->bind(':genero', $data['genero'] ?? null);
            $this->db->bind(':fecha_nacimiento', !empty($data['fecha_nacimiento']) ? $data['fecha_nacimiento'] : null);
        }
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function approvePending(int $userId, int $juntaId, ?int $customIdSocio = null) {
        if (!$this->hasStatusColumn()) {
            return null;
        }
        require_once APPROOT . '/core/TempPassword.php';
        $tempPwd = TempPassword::generate();
        $hashed = password_hash($tempPwd, PASSWORD_BCRYPT);

        if ($customIdSocio && $customIdSocio > 0) {
            $idSocio = $customIdSocio;
        } else {
            $this->db->query("SELECT MAX(id_socio) as max_id FROM usuarios WHERE junta_id = :junta_id");
            $this->db->bind(':junta_id', $juntaId);
            $row = $this->db->single();
            $idSocio = ($row && $row->max_id) ? (int)$row->max_id + 1 : 1;
        }

        $this->db->query("UPDATE usuarios SET password = :pwd, status = 'active', id_socio = :id_socio, must_change = 1 WHERE id = :id AND status = 'pending'");
        $this->db->bind(':pwd', $hashed);
        $this->db->bind(':id_socio', $idSocio);
        $this->db->bind(':id', $userId);
        if (!$this->db->execute()) {
            return null;
        }
        return $tempPwd;
    }

    public function rejectPending(int $userId, int $juntaId) {
        if (!$this->hasStatusColumn()) {
            return false;
        }
        $this->db->query("DELETE FROM usuarios WHERE id = :id AND junta_id = :junta_id AND status = 'pending'");
        $this->db->bind(':id', $userId);
        $this->db->bind(':junta_id', $juntaId);
        return $this->db->execute();
    }
}

