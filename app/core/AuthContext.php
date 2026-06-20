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
        $_SESSION['permiso_mapa_socios'] = (int)($membership->permiso_mapa_socios ?? 0);
        $_SESSION['permiso_flujo_caja'] = (int)($membership->permiso_flujo_caja ?? 0);
        $_SESSION['permiso_documentos'] = (int)($membership->permiso_documentos ?? 0);
        $_SESSION['permiso_reuniones'] = (int)($membership->permiso_reuniones ?? 0);
        $_SESSION['permiso_votaciones'] = (int)($membership->permiso_votaciones ?? 0);
        $_SESSION['mapa_socios_habilitado'] = (int)($membership->mapa_socios_habilitado ?? 0);
        $_SESSION['flujo_caja_habilitado'] = (int)($membership->flujo_caja_habilitado ?? 0);
        $_SESSION['documentos_habilitado'] = (int)($membership->documentos_habilitado ?? 0);
        $_SESSION['must_change'] = $user->must_change ?? false;
        self::syncAccountCompletionFlag($user);
        $_SESSION['user_junta_nombre'] = $membership->junta_nombre ?? 'Organización';
        $_SESSION['user_junta_comuna'] = $membership->comuna ?? '';
        $_SESSION['user_junta_tipo'] = $membership->junta_tipo ?? 'Junta de Vecinos';
        $_SESSION['user_junta_lat_sede'] = $membership->lat_sede ?? null;
        $_SESSION['user_junta_lng_sede'] = $membership->lng_sede ?? null;
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
        $_SESSION['permiso_mapa_socios'] = 0;
        $_SESSION['permiso_flujo_caja'] = 0;
        $_SESSION['permiso_documentos'] = 0;
        $_SESSION['permiso_reuniones'] = 0;
        $_SESSION['permiso_votaciones'] = 0;
        $_SESSION['mapa_socios_habilitado'] = 0;
        $_SESSION['flujo_caja_habilitado'] = 0;
        $_SESSION['documentos_habilitado'] = 0;
        $_SESSION['must_change'] = $user->must_change ?? false;
        $_SESSION['user_junta_nombre'] = 'Global';
    }

    public static function isFullAdmin() {
        return ($_SESSION['user_rol'] ?? '') === 'admin';
    }

    public static function canManageSocios() {
        // Incluye padrón de socios, calles de jurisdicción, cuotas e invitaciones.
        if (self::isFullAdmin()) return true;
        if (!empty($_SESSION['permiso_todos'])) return true;
        return !empty($_SESSION['permiso_gestion_socios']);
    }

    public static function canRegisterPayments() {
        if (self::isFullAdmin()) return true;
        if (!empty($_SESSION['permiso_todos'])) return true;
        return !empty($_SESSION['permiso_registro_pagos']);
    }

    public static function isFlujoCajaEnabled() {
        return !empty($_SESSION['flujo_caja_habilitado']);
    }

    public static function canViewFlujoCaja() {
        if (self::isFullAdmin()) {
            return true;
        }
        if (($_SESSION['user_rol'] ?? '') === 'maestro') {
            return false;
        }
        if (!self::isFlujoCajaEnabled()) {
            return false;
        }
        if (!empty($_SESSION['permiso_todos'])) {
            return true;
        }
        return !empty($_SESSION['permiso_flujo_caja']);
    }

    public static function isMapaSociosEnabled() {
        return !empty($_SESSION['mapa_socios_habilitado']);
    }

    public static function canViewMapaSocios() {
        if (!self::isMapaSociosEnabled()) {
            return false;
        }
        if (($_SESSION['user_rol'] ?? '') === 'maestro') {
            return false;
        }
        // Todos los miembros activos de la organización (administrador y socios).
        return !empty($_SESSION['user_junta_id']);
    }

    public static function isDocumentosEnabled(): bool {
        return !empty($_SESSION['documentos_habilitado']);
    }

    public static function isDirectivo(): bool {
        if (self::isFullAdmin()) {
            return true;
        }
        if (!empty($_SESSION['permiso_todos'])) {
            return true;
        }
        $cargo = strtoupper((string)($_SESSION['user_cargo'] ?? ''));
        return in_array($cargo, ['SECRETARIO', 'TESORERO', 'DIRECTOR'], true);
    }

    public static function canViewDocumentos(): bool {
        if (self::isFullAdmin()) {
            return true;
        }
        if (($_SESSION['user_rol'] ?? '') === 'maestro') {
            return false;
        }
        if (empty($_SESSION['user_junta_id'])) {
            return false;
        }
        return self::isDocumentosEnabled();
    }

    public static function canUploadDocumentos(): bool {
        if (self::isFullAdmin()) {
            return true;
        }
        if (!self::isDocumentosEnabled()) {
            return false;
        }
        if (!empty($_SESSION['permiso_todos'])) {
            return true;
        }
        return !empty($_SESSION['permiso_documentos']);
    }

    public static function canViewDocumentoVisibilidad(string $visibilidad): bool {
        if (!self::canViewDocumentos()) {
            return false;
        }
        if ($visibilidad === 'publico') {
            return true;
        }
        return self::isDirectivo();
    }

    public static function canManageReuniones(): bool {
        if (self::isFullAdmin()) {
            return true;
        }
        if (!empty($_SESSION['permiso_todos'])) {
            return true;
        }
        return !empty($_SESSION['permiso_reuniones']);
    }

    public static function canManageVotaciones(): bool {
        if (self::isFullAdmin()) {
            return true;
        }
        if (!empty($_SESSION['permiso_todos'])) {
            return true;
        }
        return !empty($_SESSION['permiso_votaciones']);
    }

    /**
     * Recarga permisos y flag del mapa desde BD (evita tener que cerrar sesión tras delegar).
     */
    public static function refreshMembershipSession(): void {
        if (empty($_SESSION['user_id']) || ($_SESSION['user_rol'] ?? '') === 'maestro') {
            return;
        }

        require_once APPROOT . '/models/Membresia.php';
        require_once APPROOT . '/models/JuntaVecinos.php';
        $memModel = new Membresia();
        $juntaModel = new JuntaVecinos();

        if (!empty($_SESSION['user_junta_id']) && $juntaModel->hasMapaSociosColumn()) {
            $junta = $juntaModel->getJuntaById((int)$_SESSION['user_junta_id']);
            if ($junta) {
                $_SESSION['mapa_socios_habilitado'] = (int)($junta->mapa_socios_habilitado ?? 0);
            }
        }
        if (!empty($_SESSION['user_junta_id']) && $juntaModel->hasFlujoCajaColumn()) {
            if (!isset($junta)) {
                $junta = $juntaModel->getJuntaById((int)$_SESSION['user_junta_id']);
            }
            if ($junta) {
                $_SESSION['flujo_caja_habilitado'] = (int)($junta->flujo_caja_habilitado ?? 0);
            }
        }

        if (!empty($_SESSION['user_junta_id']) && $juntaModel->hasDocumentosColumn()) {
            if (!isset($junta)) {
                $junta = $juntaModel->getJuntaById((int)$_SESSION['user_junta_id']);
            }
            if ($junta) {
                $_SESSION['documentos_habilitado'] = (int)($junta->documentos_habilitado ?? 0);
            }
        }

        if (self::isFullAdmin()) {
            return;
        }

        $membership = null;
        if (!empty($_SESSION['membership_id'])) {
            $membership = $memModel->getById((int)$_SESSION['membership_id']);
        }
        if (!$membership && !empty($_SESSION['user_junta_id'])) {
            $membership = $memModel->getByUsuarioJunta((int)$_SESSION['user_id'], (int)$_SESSION['user_junta_id']);
            if ($membership) {
                $_SESSION['membership_id'] = $membership->id;
            }
        }
        if (!$membership) {
            return;
        }

        $_SESSION['user_cargo'] = $membership->cargo ?? null;
        $_SESSION['permiso_gestion_socios'] = (int)($membership->permiso_gestion_socios ?? 0);
        $_SESSION['permiso_registro_pagos'] = (int)($membership->permiso_registro_pagos ?? 0);
        $_SESSION['permiso_todos'] = (int)($membership->permiso_todos ?? 0);
        if ($memModel->hasPermisoMapaColumn()) {
            $_SESSION['permiso_mapa_socios'] = (int)($membership->permiso_mapa_socios ?? 0);
        }
        if ($memModel->hasPermisoFlujoColumn()) {
            $_SESSION['permiso_flujo_caja'] = (int)($membership->permiso_flujo_caja ?? 0);
        }
        if ($memModel->hasPermisoDocumentosColumn()) {
            $_SESSION['permiso_documentos'] = (int)($membership->permiso_documentos ?? 0);
        }
        if ($memModel->hasPermisoReunionesColumn()) {
            $_SESSION['permiso_reuniones'] = (int)($membership->permiso_reuniones ?? 0);
        }
        if ($memModel->hasPermisoVotacionesColumn()) {
            $_SESSION['permiso_votaciones'] = (int)($membership->permiso_votaciones ?? 0);
        }
        if ($memModel->hasMapaSociosJuntaColumn()) {
            $_SESSION['mapa_socios_habilitado'] = (int)($membership->mapa_socios_habilitado ?? 0);
        }
        if ($memModel->hasFlujoCajaJuntaColumn()) {
            $_SESSION['flujo_caja_habilitado'] = (int)($membership->flujo_caja_habilitado ?? 0);
        }
        if ($memModel->hasDocumentosJuntaColumn()) {
            $_SESSION['documentos_habilitado'] = (int)($membership->documentos_habilitado ?? 0);
        }
    }

    public static function adminMethodsForMaestroFinanzas(): array {
        return [
            'finanzas', 'flujo_caja', 'guardar_saldo_inicial',
            'registrar_pago_cuota', 'registrar_transaccion', 'transaccion_actualizar', 'transaccion_eliminar',
            'get_socio_cuotas', 'conceptos_caja', 'concepto_caja', 'concepto_caja_crear',
            'concepto_caja_actualizar', 'concepto_caja_eliminar', 'comprobante',
        ];
    }

    public static function adminMethodsForSocioDelegado() {
        $methods = [];
        if (self::canViewMapaSocios()) {
            $methods[] = 'mapa_socios';
        }
        if (self::canManageSocios()) {
            $methods = array_merge($methods, ['socios', 'socio_crear', 'socio_actualizar', 'socio_reset_password', 'socio_eliminar', 'socio_reactivar', 'calle_crear', 'calle_eliminar', 'cuota_ajustar', 'socio_delegacion', 'generar_invitacion', 'invitacion_revocar', 'socio_pendiente_actualizar', 'socio_pendiente_aprobar', 'socio_pendiente_rechazar', 'socio_importar_validar', 'socio_importar_confirmar', 'socio_importar_chunk', 'socio_prevalidar_actualizar', 'socio_prevalidar_aprobar', 'socio_prevalidar_eliminar', 'cambio_aprobar', 'cambio_rechazar', 'cambio_actualizar']);
        }
        if (self::canRegisterPayments()) {
            $methods = array_merge($methods, [
                'finanzas', 'registrar_pago_cuota', 'registrar_transaccion', 'transaccion_actualizar', 'transaccion_eliminar', 'get_socio_cuotas',
                'guardar_saldo_inicial', 'conceptos_caja', 'concepto_caja', 'concepto_caja_crear',
                'concepto_caja_actualizar', 'concepto_caja_eliminar',
            ]);
        }
        if (self::canViewFlujoCaja()) {
            $methods[] = 'flujo_caja';
        }
        if (self::canViewDocumentos()) {
            $methods = array_merge($methods, [
                'documentos', 'documento_ver', 'documento_archivo', 'documento_descargar',
            ]);
        }
        if (self::canUploadDocumentos()) {
            $methods = array_merge($methods, [
                'documento_subir', 'documento_eliminar',
                'documento_categoria_crear', 'documento_categoria_actualizar', 'documento_categoria_eliminar',
            ]);
        }
        if (self::canManageReuniones()) {
            $methods = array_merge($methods, [
                'asistencia', 'reunion_crear', 'reunion_actualizar', 'reunion_resultados',
                'asistencia_guardar', 'asistencia_qr_registrar', 'reunion_minuta', 'reunion_reenviar_rsvp',
            ]);
        }
        if (self::canManageVotaciones()) {
            $methods = array_merge($methods, [
                'votaciones', 'votacion_crear', 'votacion_actualizar', 'votacion_ver',
                'votacion_publicar', 'votacion_cerrar', 'votacion_votar',
            ]);
        }
        if (self::isDirectivo() || self::canManageReuniones()) {
            $methods[] = 'calendario';
        }
        if (self::isDirectivo()) {
            $methods = array_merge($methods, [
                'documentacion_legal', 'junta_documento_legal_subir',
                'junta_documento_legal_eliminar', 'junta_documento_legal_descargar',
                'votacion_ver',
            ]);
        }
        return array_unique($methods);
    }

    public static function syncAccountCompletionFlag($user): void {
        require_once APPROOT . '/core/InviteRutCheck.php';
        $_SESSION['needs_account_completion'] = (
            ($user->status ?? '') === 'prevalidar'
            && InviteRutCheck::isPlaceholderEmail($user->email ?? '')
        ) ? 1 : 0;
    }
}
