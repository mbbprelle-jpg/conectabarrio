<?php
/**
 * Códigos QR de asistencia — formato: CBASIST:v1:{token}
 */
class AsistenciaQr {
    public const PREFIX = 'CBASIST:v1:';

    public static function generateToken(): string {
        return bin2hex(random_bytes(24));
    }

    public static function buildPayload(string $token): string {
        return self::PREFIX . $token;
    }

    /** Extrae el token desde payload escaneado (QR, URL o texto plano). */
    public static function parseScannedText(string $raw): ?string {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }
        if (str_starts_with($raw, self::PREFIX)) {
            $token = substr($raw, strlen(self::PREFIX));
            return self::isValidTokenFormat($token) ? $token : null;
        }
        if (preg_match('/[?&]token=([a-f0-9]{48})/i', $raw, $m)) {
            return strtolower($m[1]);
        }
        if (self::isValidTokenFormat($raw)) {
            return strtolower($raw);
        }
        return null;
    }

    public static function isValidTokenFormat(string $token): bool {
        return (bool)preg_match('/^[a-f0-9]{48}$/i', $token);
    }
}
