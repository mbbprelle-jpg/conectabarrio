<?php
class AuthContext {

    public static function applyMembership($membership, $user) {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_rut'] = $user->rut;
        $_SESSION['user_nombre'] = $user->nombre;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_rol'] = $membership->rol;
        $_SESSION['user_junta_id'] = $membership->junta_id;
        $_SESSION['membership_id'] = $membership->id;
        $_SESSION['user_cargo'] = $membership->cargo ?? null;
        $_SESSION['permiso_gestion_socios'] = (int)($membership->permiso_gestion_socios ?? 0);
        $_SESSION['permiso_registro_pagos'] = (int)($membership->permiso_registro_pagos ?? 0);
        $_SESSION['permiso_todos'] = (int)($membership->permiso_todos ?? 0);
        $_SESSION['must_change'] = $user->must_change ?? false;
        $_SESSION['user_junta_nombre'] = $membership->junta_nombre ?? 'Organización';
        $_SESSION['user_junta_comuna'] = $membership->comuna ?? '';
        $_SESSION['user_junta_plan'] = $membership->plan ?? 'basico';
        $_SESSION['user_junta_precio_anual'] = $membership->precio_anual ?? 0;
    }

    public static function applyMaestro($user) {
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_rut'] = $user->rut;
        $_SESSION['user_nombre'] = $user->nombre;
        $_SESSION['user_email'] = $user->email;
        $_SESSION['user_rol'] = 'maestro';
        $_SESSION['user_junta_id'] = null;
        $_SESSION['membership_id'] = null;
        $_SESSION['user_cargo'] = null;
        $_SESSION['permiso_gestion_socios'] = 0;
        $_SESSION['permiso_registro_pagos'] = 0;
        $_SESSION['permiso_todos'] = 0;
        $_SESSION['must_change'] = $user->must_change ?? false;
        $_SESSION['user_junta_nombre'] = 'Global';
    }

    public static function isFullAdmin() {
        return ($_SESSION['user_rol'] ?? '') === 'admin';
    }

    public static function canManageSocios() {
        if (self::isFullAdmin()) return true;
        if (!empty($_SESSION['permiso_todos'])) return true;
        return !empty($_SESSION['permiso_gestion_socios']);
    }

    public static function canRegisterPayments() {
        if (self::isFullAdmin()) return true;
        if (!empty($_SESSION['permiso_todos'])) return true;
        return !empty($_SESSION['permiso_registro_pagos']);
    }

    public static function adminMethodsForSocioDelegado() {
        $methods = [];
        if (self::canManageSocios()) {
            $methods = array_merge($methods, ['socios', 'socio_crear', 'socio_actualizar', 'socio_reset_password', 'socio_eliminar', 'socio_reactivar', 'calle_crear', 'calle_eliminar', 'cuota_ajustar', 'socio_delegacion']);
        }
        if (self::canRegisterPayments()) {
            $methods = array_merge($methods, ['finanzas', 'registrar_pago_cuota', 'registrar_transaccion', 'get_socio_cuotas']);
        }
        return array_unique($methods);
    }
}
