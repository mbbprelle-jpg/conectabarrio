<?php
class RutChile {

    public static function clean($rut) {
        return strtoupper(str_replace(['.', ' ', '-'], '', trim((string)$rut)));
    }

    public static function format($rut) {
        $clean = self::clean($rut);
        if (strlen($clean) < 2) {
            return $clean;
        }
        $body = substr($clean, 0, -1);
        $dv = substr($clean, -1);
        return $body . '-' . $dv;
    }

    public static function validate($rut) {
        $formatted = self::format($rut);
        if (!preg_match('/^(\d+)-([\dK])$/', $formatted, $m)) {
            return false;
        }
        $body = $m[1];
        $dv = $m[2];
        if (strlen($body) < 7 || strlen($body) > 9) {
            return false;
        }
        $sum = 0;
        $mult = 2;
        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $sum += (int)$body[$i] * $mult;
            $mult = ($mult === 7) ? 2 : $mult + 1;
        }
        $rest = 11 - ($sum % 11);
        $expected = $rest === 11 ? '0' : ($rest === 10 ? 'K' : (string)$rest);
        return $dv === $expected;
    }

    public static function normalize($rut) {
        if (!self::validate($rut)) {
            return false;
        }
        return self::format($rut);
    }
}
