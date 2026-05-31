<?php
require_once APPROOT . '/core/RutChile.php';

class InviteRutCheck {

    public const ACTION_REGISTER = 'register';
    public const ACTION_COMPLETE_PREVALIDAR = 'complete_prevalidar';
    public const ACTION_BLOCKED = 'blocked';

    /**
     * Evalúa si un RUT puede avanzar con el registro por invitación en una organización.
     */
    public static function evaluate(User $userModel, Membresia $membresiaModel, string $rut, int $juntaId): array {
        $normalized = RutChile::normalize($rut);
        if ($normalized === false) {
            return self::blocked($rut, 'RUT inválido', 'Use el formato 126667777-6 (sin puntos ni espacios).');
        }

        $user = $userModel->findUserByRut($normalized);
        if (!$user) {
            return [
                'action' => self::ACTION_REGISTER,
                'rut' => $normalized,
                'title' => '',
                'message' => '',
                'detail' => '',
                'prefill' => ['rut' => $normalized],
                'user' => null,
            ];
        }

        if (!self::userBelongsToJunta($user, $membresiaModel, $juntaId)) {
            return self::blocked(
                $normalized,
                'RUT ya registrado en el sistema',
                'Este RUT pertenece a otra organización. Si debe afiliarse aquí, contacte a la directiva de su junta de vecinos.'
            );
        }

        $status = $user->status ?? 'active';
        $estado = (int)($user->estado ?? 1);

        if ($status === 'prevalidar') {
            return [
                'action' => self::ACTION_COMPLETE_PREVALIDAR,
                'rut' => $normalized,
                'title' => 'Datos pre-cargados',
                'message' => 'Encontramos su ficha en la organización. Revise, complete y confirme la información.',
                'detail' => '',
                'prefill' => self::userToPrefill($user),
                'user' => $user,
            ];
        }

        if ($status === 'pending') {
            return self::blocked(
                $normalized,
                'Solicitud pendiente de aprobación',
                'Su registro ya fue enviado y está a la espera de revisión por la directiva. Recibirá un correo cuando sea aprobado.'
            );
        }

        if ($status === 'active' && $estado === 1) {
            $idSocio = !empty($user->id_socio) ? ' #' . (int)$user->id_socio : '';
            return self::blocked(
                $normalized,
                'Ya está registrado como socio activo',
                'Su cuenta en esta organización está activa' . $idSocio . '. Puede iniciar sesión con su RUT o correo.'
            );
        }

        if ($estado === 0) {
            return self::blocked(
                $normalized,
                'Cuenta dada de baja',
                'Su registro en esta organización está inactivo. Contacte a la directiva si necesita reactivar su afiliación.'
            );
        }

        return self::blocked(
            $normalized,
            'No puede registrarse con este RUT',
            'Existe un registro previo en esta organización. Contacte a la directiva para más información.'
        );
    }

    private static function userBelongsToJunta($user, Membresia $membresiaModel, int $juntaId): bool {
        if ((int)($user->junta_id ?? 0) === $juntaId) {
            return true;
        }
        $mem = $membresiaModel->getByUsuarioJunta((int)$user->id, $juntaId);
        return $mem && (int)$mem->estado === 1;
    }

    private static function userToPrefill($user): array {
        return [
            'rut' => $user->rut ?? '',
            'id_socio' => $user->id_socio ?? '',
            'nombres' => $user->nombre ?? '',
            'apellido_paterno' => $user->apellido_paterno ?? '',
            'apellido_materno' => $user->apellido_materno ?? '',
            'email' => self::displayEmail($user->email ?? ''),
            'telefono' => $user->telefono ?? '',
            'genero' => $user->genero ?? '',
            'fecha_nacimiento' => !empty($user->fecha_nacimiento) ? substr($user->fecha_nacimiento, 0, 10) : '',
            'estado_civil' => $user->estado_civil ?? '',
            'nacionalidad' => $user->nacionalidad ?? '',
            'profesion' => $user->profesion ?? '',
            'calle_id' => $user->calle_id ?? '',
            'numero_casa' => $user->numero_casa ?? '',
            'fecha_inicio' => !empty($user->fecha_inicio) ? substr($user->fecha_inicio, 0, 10) : date('Y-m-d'),
            'prevalidar_user_id' => (int)$user->id,
        ];
    }

    /** Oculta correos placeholder de pre-validación. */
    public static function displayEmail(?string $email): string {
        $email = trim((string)$email);
        if ($email === '' || str_contains($email, '@prevalidar.conectabarrio')) {
            return '';
        }
        return $email;
    }

    public static function placeholderEmail(string $rut, int $juntaId): string {
        $digits = preg_replace('/\D/', '', $rut);
        return 'prevalidar.' . $juntaId . '.' . $digits . '@prevalidar.conectabarrio';
    }

    private static function blocked(string $rut, string $title, string $detail): array {
        return [
            'action' => self::ACTION_BLOCKED,
            'rut' => $rut,
            'title' => $title,
            'message' => $title,
            'detail' => $detail,
            'prefill' => ['rut' => $rut],
            'user' => null,
        ];
    }
}
