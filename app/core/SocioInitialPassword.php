<?php
/**
 * Clave inicial para socios en alta provisional (sin correo real).
 * Usa los primeros 6 dígitos del cuerpo numérico del RUT (sin dígito verificador).
 */
class SocioInitialPassword {

    public static function fromRut(string $rut): string {
        $digits = preg_replace('/\D/', '', $rut);
        if ($digits === '') {
            return '000000';
        }
        $body = strlen($digits) > 1 ? substr($digits, 0, -1) : $digits;
        $pwd = substr($body, 0, 6);
        if (strlen($pwd) < 6) {
            $pwd = str_pad($pwd, 6, '0', STR_PAD_LEFT);
        }
        return $pwd;
    }

    public static function hashForRut(string $rut): string {
        return password_hash(self::fromRut($rut), PASSWORD_BCRYPT);
    }
}
