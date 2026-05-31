<?php
class SocioInput {

    public const GENEROS = ['MASCULINO', 'FEMENINO', 'NO ESPECIFICAR'];

    public static function normalizeTextFields(array $data, array $skipKeys = ['email', 'password', 'token', 'calle_id', 'fecha_inicio', 'fecha_nacimiento', 'genero']) {
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

    public static function generoLabel($genero) {
        $map = [
            'MASCULINO' => 'Masculino',
            'FEMENINO' => 'Femenino',
            'NO ESPECIFICAR' => 'No especificar',
        ];
        return $map[$genero] ?? $genero;
    }
}
