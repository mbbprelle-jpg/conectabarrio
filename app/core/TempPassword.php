<?php
class TempPassword {

    public static function generate($length = 12) {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $max = strlen($chars) - 1;
        $pass = '';
        for ($i = 0; $i < $length; $i++) {
            $pass .= $chars[random_int(0, $max)];
        }
        return $pass;
    }

    /**
     * Genera clave temporal, la envía por correo y solo entonces la guarda (must_change = 1).
     * El administrador nunca recibe la contraseña en texto plano.
     *
     * @return array{ok: bool, error?: string}
     */
    public static function issueToUser($user) {
        if (empty($user->email) || !filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'El usuario no tiene un correo electrónico válido registrado.'];
        }
        if (!Mailer::isConfigured()) {
            return ['ok' => false, 'error' => 'El servidor de correo no está configurado. Contacte al soporte de ConectaBarrio.'];
        }

        $plain = self::generate();
        $mailResult = TempPasswordMail::send($user, $plain);
        if (!$mailResult['ok']) {
            return ['ok' => false, 'error' => 'No se pudo enviar el correo: ' . ($mailResult['error'] ?? 'error desconocido')];
        }

        $userModel = new User();
        $hash = password_hash($plain, PASSWORD_BCRYPT);
        if (!$userModel->setTempPassword($user->id, $hash)) {
            return ['ok' => false, 'error' => 'El correo se envió pero no se pudo guardar la clave en el sistema. Contacte soporte.'];
        }

        unset($plain, $hash);
        return ['ok' => true];
    }
}
