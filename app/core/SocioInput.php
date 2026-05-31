<?php
class SocioInput {

    public const GENEROS = ['MASCULINO', 'FEMENINO', 'NO ESPECIFICAR'];

    public const ESTADOS_CIVILES = [
        'SOLTERO' => 'Soltero/a',
        'CASADO' => 'Casado/a',
        'CONVIVIENTE_CIVIL' => 'Conviviente Civil',
        'DIVORCIADO' => 'Divorciado/a',
        'VIUDO' => 'Viudo/a',
    ];

    public const NACIONALIDADES = [
        'Chile',
        'Argentina',
        'Belice',
        'Bolivia',
        'Brasil',
        'Colombia',
        'Costa Rica',
        'Cuba',
        'Ecuador',
        'El Salvador',
        'Guatemala',
        'Guyana',
        'Haití',
        'Honduras',
        'México',
        'Nicaragua',
        'Panamá',
        'Paraguay',
        'Perú',
        'Puerto Rico',
        'República Dominicana',
        'Surinam',
        'Uruguay',
        'Venezuela',
    ];

    public static function normalizeTextFields(array $data, array $skipKeys = []) {
        $defaultSkip = ['email', 'password', 'token', 'calle_id', 'fecha_inicio', 'fecha_nacimiento', 'genero', 'estado_civil', 'nacionalidad', 'telefono'];
        $skipKeys = array_unique(array_merge($defaultSkip, $skipKeys));
        foreach ($data as $key => $value) {
            if (in_array($key, $skipKeys, true) || !is_string($value)) {
                continue;
            }
            $data[$key] = mb_strtoupper(trim($value), 'UTF-8');
        }
        return $data;
    }

    public static function normalizeGenero($genero) {
        $g = mb_strtoupper(trim((string)$genero), 'UTF-8');
        return in_array($g, self::GENEROS, true) ? $g : null;
    }

    public static function normalizeEstadoCivil($estadoCivil) {
        $key = mb_strtoupper(trim((string)$estadoCivil), 'UTF-8');
        return array_key_exists($key, self::ESTADOS_CIVILES) ? $key : null;
    }

    public static function normalizeNacionalidad($nacionalidad) {
        $value = trim((string)$nacionalidad);
        if ($value === '') {
            return null;
        }
        foreach (self::NACIONALIDADES as $pais) {
            if (mb_strtolower($pais, 'UTF-8') === mb_strtolower($value, 'UTF-8')) {
                return $pais;
            }
        }
        return null;
    }

    public static function normalizeTelefono($telefono) {
        $digits = preg_replace('/\D+/', '', (string)$telefono);
        if ($digits === '') {
            return '';
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '56')) {
            $digits = substr($digits, 2);
        }
        if (strlen($digits) !== 9) {
            return '';
        }
        return '+56' . $digits;
    }

    public static function telefonoDigits($telefono) {
        $digits = preg_replace('/\D+/', '', (string)$telefono);
        if ($digits === '') {
            return '';
        }
        if (strlen($digits) === 11 && str_starts_with($digits, '56')) {
            return substr($digits, 2);
        }
        if (strlen($digits) === 9) {
            return $digits;
        }
        return '';
    }

    public static function isValidTelefono($telefono) {
        if ($telefono === '' || $telefono === null) {
            return true;
        }
        return (bool)preg_match('/^\+56[0-9]{9}$/', (string)$telefono);
    }

    public static function formatTelefonoDisplay($telefono) {
        $digits = self::telefonoDigits($telefono);
        if ($digits === '') {
            return '';
        }
        return '+56 ' . $digits;
    }

    public static function parseProfileFromPost(array $post) {
        return [
            'genero' => self::normalizeGenero($post['genero'] ?? ''),
            'fecha_nacimiento' => !empty($post['fecha_nacimiento']) ? $post['fecha_nacimiento'] : null,
            'estado_civil' => self::normalizeEstadoCivil($post['estado_civil'] ?? ''),
            'nacionalidad' => self::normalizeNacionalidad($post['nacionalidad'] ?? ''),
            'telefono' => self::normalizeTelefono($post['telefono'] ?? ''),
        ];
    }

    public static function validateProfile(array $data, $requireAll = true) {
        if ($requireAll) {
            if (empty($data['genero'])) {
                return 'Seleccione el género.';
            }
            if (empty($data['fecha_nacimiento'])) {
                return 'Indique la fecha de nacimiento.';
            }
            if (empty($data['estado_civil'])) {
                return 'Seleccione el estado civil.';
            }
            if (empty($data['nacionalidad'])) {
                return 'Seleccione la nacionalidad.';
            }
        }
        if (!self::isValidTelefono($data['telefono'] ?? '')) {
            return 'El teléfono debe tener 9 dígitos (ej: 912345678).';
        }
        return null;
    }

    public static function generoLabel($genero) {
        $map = [
            'MASCULINO' => 'Masculino',
            'FEMENINO' => 'Femenino',
            'NO ESPECIFICAR' => 'No especificar',
        ];
        return $map[$genero] ?? $genero;
    }

    public static function estadoCivilLabel($estadoCivil) {
        return self::ESTADOS_CIVILES[$estadoCivil] ?? $estadoCivil;
    }
}
