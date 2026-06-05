<?php
require_once APPROOT . '/core/RutChile.php';
require_once APPROOT . '/core/SocioInput.php';
require_once APPROOT . '/core/SocioGeoref.php';
require_once APPROOT . '/core/InviteRutCheck.php';

class SocioBulkImport {

    private const COLUMN_ALIASES = [
        'id_socio' => ['id_socio', 'idsocio', 'id socio', 'n socio', 'n° socio'],
        'rut' => ['rut'],
        'nombres' => ['nombres', 'nombre'],
        'apellido_paterno' => ['apellido_paterno', 'apellido paterno', 'paterno'],
        'apellido_materno' => ['apellido_materno', 'apellido materno', 'materno'],
        'email' => ['email', 'correo', 'correo electronico', 'correo electrónico'],
        'telefono' => ['telefono', 'teléfono', 'fono'],
        'genero' => ['genero', 'género', 'sexo'],
        'fecha_nacimiento' => ['fecha_nacimiento', 'fecha nacimiento', 'nacimiento', 'fnac'],
        'estado_civil' => ['estado_civil', 'estado civil'],
        'nacionalidad' => ['nacionalidad'],
        'profesion' => ['profesion', 'profesión', 'oficio'],
        'calle' => ['calle', 'direccion', 'dirección'],
        'numero_casa' => ['numero_casa', 'numero casa', 'n casa', 'n° casa', 'numero'],
        'fecha_inicio' => ['fecha_inicio', 'fecha inicio', 'inicio'],
        'latitud' => ['latitud', 'lat'],
        'longitud' => ['longitud', 'lng', 'lon'],
        'link_google' => ['link_google', 'link google', 'google maps', 'maps'],
    ];

    /**
     * @param array<int, object> $calles calles de la junta (id, nombre)
     */
    public static function parse(string $raw, array $calles, int $juntaId): array {
        $lines = preg_split('/\r\n|\r|\n/', trim($raw));
        $lines = array_values(array_filter($lines, fn($l) => trim($l) !== ''));
        if (empty($lines)) {
            return ['rows' => [], 'valid_count' => 0, 'error_count' => 0, 'message' => 'No hay filas para procesar.'];
        }

        $delimiter = self::detectDelimiter($lines[0]);
        $firstCells = str_getcsv($lines[0], $delimiter);
        $map = self::buildColumnMap($firstCells);
        $startIndex = $map !== null ? 1 : 0;
        if ($map === null) {
            $map = self::defaultColumnMap(count(str_getcsv($lines[0], $delimiter)));
        }

        $calleIndex = self::buildCalleIndex($calles);
        $rows = [];
        $validCount = 0;
        $errorCount = 0;

        for ($i = $startIndex; $i < count($lines); $i++) {
            $cells = str_getcsv($lines[$i], $delimiter);
            $lineNum = $i + 1;
            $parsed = self::mapRow($cells, $map, $calleIndex, $juntaId);
            $parsed['line'] = $lineNum;
            $parsed['errors'] = self::validateRow($parsed['data'], $juntaId);
            $parsed['valid'] = empty($parsed['errors']);
            if ($parsed['valid']) {
                $validCount++;
            } else {
                $errorCount++;
            }
            $rows[] = $parsed;
        }

        return [
            'rows' => $rows,
            'valid_count' => $validCount,
            'error_count' => $errorCount,
            'message' => '',
        ];
    }

    private static function detectDelimiter(string $line): string {
        if (str_contains($line, "\t")) {
            return "\t";
        }
        if (substr_count($line, ';') > substr_count($line, ',')) {
            return ';';
        }
        return ',';
    }

    private static function normalizeHeader(string $h): string {
        $h = mb_strtolower(trim($h), 'UTF-8');
        $h = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $h);
        return preg_replace('/\s+/', ' ', $h);
    }

    private static function buildColumnMap(array $cells): ?array {
        $normalized = array_map([self::class, 'normalizeHeader'], $cells);
        $matched = 0;
        foreach (self::COLUMN_ALIASES as $field => $aliases) {
            foreach ($normalized as $h) {
                if (in_array($h, $aliases, true)) {
                    $matched++;
                    break;
                }
            }
        }
        if ($matched < 3) {
            return null;
        }
        $map = [];
        foreach ($normalized as $idx => $h) {
            foreach (self::COLUMN_ALIASES as $field => $aliases) {
                if (in_array($h, $aliases, true)) {
                    $map[$field] = $idx;
                }
            }
        }
        return $map;
    }

    /** Orden fijo si no hay encabezados. */
    private static function defaultColumnMap(int $colCount): array {
        $fields = array_keys(self::COLUMN_ALIASES);
        $map = [];
        for ($i = 0; $i < min($colCount, count($fields)); $i++) {
            $map[$fields[$i]] = $i;
        }
        return $map;
    }

    private static function buildCalleIndex(array $calles): array {
        $index = [];
        foreach ($calles as $c) {
            $key = mb_strtoupper(trim($c->nombre), 'UTF-8');
            $index[$key] = (int)$c->id;
        }
        return $index;
    }

    private static function cell(array $cells, array $map, string $field): string {
        if (!isset($map[$field])) {
            return '';
        }
        $idx = $map[$field];
        return isset($cells[$idx]) ? trim((string)$cells[$idx]) : '';
    }

    private static function mapRow(array $cells, array $map, array $calleIndex, int $juntaId): array {
        $rutRaw = self::cell($cells, $map, 'rut');
        $rut = RutChile::normalize($rutRaw) ?: trim($rutRaw);
        $email = mb_strtolower(self::cell($cells, $map, 'email'), 'UTF-8');
        if ($email === '') {
            $email = InviteRutCheck::placeholderEmail($rut !== '' ? $rut : uniqid('tmp', true), $juntaId);
        }

        $calleName = mb_strtoupper(self::cell($cells, $map, 'calle'), 'UTF-8');
        $calleId = $calleIndex[$calleName] ?? null;

        $generoRaw = mb_strtoupper(self::cell($cells, $map, 'genero'), 'UTF-8');
        $estadoCivilRaw = mb_strtoupper(self::cell($cells, $map, 'estado_civil'), 'UTF-8');
        $estadoCivilRaw = str_replace(' ', '_', $estadoCivilRaw);

        $latRaw = self::cell($cells, $map, 'latitud');
        $lngRaw = self::cell($cells, $map, 'longitud');
        $linkRaw = mb_strtolower(self::cell($cells, $map, 'link_google'), 'UTF-8');
        $lat = SocioGeoref::parseCoord($latRaw);
        $lng = SocioGeoref::parseCoord($lngRaw);
        if ($lat !== null && $lng !== null) {
            $linkRaw = SocioGeoref::buildGoogleMapsLink($lat, $lng);
        }

        $data = [
            'junta_id' => $juntaId,
            'id_socio' => self::cell($cells, $map, 'id_socio') !== '' ? (int)self::cell($cells, $map, 'id_socio') : null,
            'rut' => $rut,
            'nombres' => self::cell($cells, $map, 'nombres'),
            'apellido_paterno' => self::cell($cells, $map, 'apellido_paterno'),
            'apellido_materno' => self::cell($cells, $map, 'apellido_materno'),
            'email' => $email,
            'telefono' => SocioInput::normalizeTelefono(self::cell($cells, $map, 'telefono')),
            'genero' => SocioInput::normalizeGenero($generoRaw),
            'fecha_nacimiento' => self::cell($cells, $map, 'fecha_nacimiento') ?: null,
            'estado_civil' => SocioInput::normalizeEstadoCivil($estadoCivilRaw),
            'nacionalidad' => SocioInput::normalizeNacionalidad(self::cell($cells, $map, 'nacionalidad')),
            'profesion' => SocioInput::normalizeProfesion(self::cell($cells, $map, 'profesion')),
            'calle_id' => $calleId,
            'numero_casa' => self::cell($cells, $map, 'numero_casa'),
            'fecha_inicio' => self::cell($cells, $map, 'fecha_inicio') ?: date('Y-m-d'),
            'latitud' => $lat,
            'longitud' => $lng,
            'link_google' => $linkRaw !== '' ? $linkRaw : null,
            'password' => '',
            'use_rut_initial_password' => true,
            'rol' => 'socio',
            'estado' => 1,
        ];

        return ['data' => SocioInput::normalizeTextFields($data)];
    }

    private static function validateRow(array $data, int $juntaId): array {
        $errors = [];
        if (RutChile::normalize($data['rut'] ?? '') === false) {
            $errors[] = 'RUT inválido';
        }
        if (($data['nombres'] ?? '') === '') {
            $errors[] = 'Falta nombre';
        }
        if (($data['apellido_paterno'] ?? '') === '') {
            $errors[] = 'Falta apellido paterno';
        }
        if (!empty($data['email']) && !str_contains($data['email'], '@prevalidar.conectabarrio')) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Correo inválido';
            }
        }
        if (!empty($data['id_socio']) && (int)$data['id_socio'] <= 0) {
            $errors[] = 'ID socio inválido';
        }
        if (!empty($data['telefono']) && !SocioInput::isValidTelefono($data['telefono'])) {
            $errors[] = 'Teléfono inválido (9 dígitos)';
        }
        if (!empty($data['calle_id']) === false && !empty($data['numero_casa'])) {
            // calle opcional en prevalidar
        }
        return $errors;
    }
}
