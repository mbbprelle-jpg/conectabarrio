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
    public static function parse(string $raw, array $calles, int $juntaId, bool $usesCalles = true): array {
        $lines = preg_split('/\r\n|\r|\n/', trim($raw));
        $lines = array_values(array_filter($lines, fn($l) => trim($l) !== ''));
        if (empty($lines)) {
            return ['rows' => [], 'valid_count' => 0, 'error_count' => 0, 'warning_count' => 0, 'message' => 'No hay filas para procesar.'];
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
        $warningCount = 0;

        for ($i = $startIndex; $i < count($lines); $i++) {
            $cells = str_getcsv($lines[$i], $delimiter);
            $lineNum = $i + 1;
            $parsed = self::mapRow($cells, $map, $calleIndex, $juntaId);
            $parsed['line'] = $lineNum;
            $validation = self::validateRow($parsed['data'], $parsed['meta'], $usesCalles);
            $parsed['errors'] = $validation['errors'];
            $parsed['warnings'] = $validation['warnings'];
            $parsed['valid'] = empty($parsed['errors']);
            if ($parsed['valid']) {
                $validCount++;
                if (!empty($parsed['warnings'])) {
                    $warningCount++;
                }
            } else {
                $errorCount++;
            }
            unset($parsed['meta']);
            $rows[] = $parsed;
        }

        return [
            'rows' => $rows,
            'valid_count' => $validCount,
            'error_count' => $errorCount,
            'warning_count' => $warningCount,
            'message' => '',
        ];
    }

    /** Elimina metadatos internos antes de persistir. */
    public static function stripInternalFields(array $data): array {
        foreach (array_keys($data) as $key) {
            if (str_starts_with((string)$key, '_')) {
                unset($data[$key]);
            }
        }
        return $data;
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
        $emailRaw = self::cell($cells, $map, 'email');
        $email = mb_strtolower($emailRaw, 'UTF-8');
        if ($email === '') {
            $email = InviteRutCheck::placeholderEmail($rut !== '' ? $rut : uniqid('tmp', true), $juntaId);
        }

        $calleNameRaw = self::cell($cells, $map, 'calle');
        $calleName = mb_strtoupper($calleNameRaw, 'UTF-8');
        $calleId = $calleIndex[$calleName] ?? null;

        $generoRaw = mb_strtoupper(self::cell($cells, $map, 'genero'), 'UTF-8');
        $estadoCivilRaw = self::cell($cells, $map, 'estado_civil');
        $nacionalidadRaw = self::cell($cells, $map, 'nacionalidad');
        $fechaNacRaw = self::cell($cells, $map, 'fecha_nacimiento');
        $fechaInicioRaw = self::cell($cells, $map, 'fecha_inicio');

        $fechaNacimiento = SocioInput::parseExcelDate($fechaNacRaw);
        $fechaInicio = SocioInput::parseExcelDate($fechaInicioRaw);
        if ($fechaInicio === null && $fechaInicioRaw === '') {
            $fechaInicio = date('Y-m-d');
        }

        $estadoCivil = SocioInput::normalizeEstadoCivil($estadoCivilRaw);
        if ($estadoCivil === null && trim($estadoCivilRaw) === '') {
            $estadoCivil = 'NO_INFORMAR';
        }

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
            'fecha_nacimiento' => $fechaNacimiento === false ? null : $fechaNacimiento,
            'estado_civil' => $estadoCivil,
            'nacionalidad' => SocioInput::normalizeNacionalidadFromImport($nacionalidadRaw),
            'profesion' => SocioInput::normalizeProfesion(self::cell($cells, $map, 'profesion')),
            'calle_id' => $calleId,
            'numero_casa' => self::cell($cells, $map, 'numero_casa'),
            'fecha_inicio' => $fechaInicio === false ? null : $fechaInicio,
            'latitud' => $lat,
            'longitud' => $lng,
            'link_google' => $linkRaw !== '' ? $linkRaw : null,
            'password' => '',
            'use_rut_initial_password' => true,
            'rol' => 'socio',
            'estado' => 1,
        ];

        $meta = [
            'genero_raw' => $generoRaw,
            'estado_civil_raw' => $estadoCivilRaw,
            'nacionalidad_raw' => $nacionalidadRaw,
            'fecha_nacimiento_raw' => $fechaNacRaw,
            'fecha_inicio_raw' => $fechaInicioRaw,
            'calle_nombre_raw' => $calleNameRaw,
            'email_raw' => $emailRaw,
            'fecha_nacimiento_invalid' => $fechaNacimiento === false,
            'fecha_inicio_invalid' => $fechaInicioRaw !== '' && $fechaInicio === false,
        ];

        return [
            'data' => SocioInput::normalizeTextFields($data),
            'meta' => $meta,
        ];
    }

    private static function validateRow(array $data, array $meta, bool $usesCalles): array {
        $errors = [];
        $warnings = [];

        if (RutChile::normalize($data['rut'] ?? '') === false) {
            $errors[] = 'RUT inválido';
        }
        if (($data['nombres'] ?? '') === '') {
            $errors[] = 'Falta nombre';
        }
        if (($data['apellido_paterno'] ?? '') === '') {
            $errors[] = 'Falta apellido paterno';
        }

        if (!empty($meta['email_raw']) && !filter_var($meta['email_raw'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Correo inválido';
        }

        if (!empty($data['id_socio']) && (int)$data['id_socio'] <= 0) {
            $errors[] = 'ID socio inválido';
        }

        if (!empty($data['telefono']) && !SocioInput::isValidTelefono($data['telefono'])) {
            $errors[] = 'Teléfono inválido (debe tener 9 dígitos, ej: 912345678)';
        }

        if ($meta['fecha_nacimiento_invalid'] ?? false) {
            $errors[] = 'Fecha de nacimiento inválida (use dd-mm-yyyy, ej: 15-03-1985)';
        }
        if ($meta['fecha_inicio_invalid'] ?? false) {
            $errors[] = 'Fecha de inicio inválida (use dd-mm-yyyy, ej: 01-02-2017)';
        }

        if (($meta['genero_raw'] ?? '') !== '' && empty($data['genero'])) {
            $errors[] = 'Género no reconocido (use MASCULINO, FEMENINO o NO ESPECIFICAR)';
        }
        if (($meta['estado_civil_raw'] ?? '') !== '' && empty($data['estado_civil'])) {
            $errors[] = 'Estado civil no reconocido (use CASADO/A, SOLTERO/A, NO INFORMAR, NO INFORMADO, etc.)';
        }
        if (($meta['nacionalidad_raw'] ?? '') !== '' && empty($data['nacionalidad'])) {
            $errors[] = 'Nacionalidad no reconocida (ej: CHILENA, ARGENTINA)';
        }

        if (($meta['email_raw'] ?? '') === '') {
            $warnings[] = 'Sin correo: se usará alta provisional (clave = primeros 6 dígitos del RUT)';
        }

        if (($data['apellido_materno'] ?? '') === '') {
            $warnings[] = 'Falta apellido materno (requerido al activar la cuenta)';
        }
        if (empty($data['fecha_nacimiento'])) {
            $warnings[] = 'Falta fecha de nacimiento (requerida al activar la cuenta)';
        }
        if (empty($data['genero']) && ($meta['genero_raw'] ?? '') === '') {
            $warnings[] = 'Falta género (requerido al activar la cuenta)';
        }
        if (empty($data['nacionalidad']) && ($meta['nacionalidad_raw'] ?? '') === '') {
            $warnings[] = 'Falta nacionalidad (requerida al activar la cuenta)';
        }
        if (empty($data['profesion'])) {
            $warnings[] = 'Falta profesión (requerida al activar la cuenta)';
        }

        if ($usesCalles) {
            $calleNombre = trim($meta['calle_nombre_raw'] ?? '');
            if ($calleNombre !== '' && empty($data['calle_id'])) {
                $errors[] = 'Calle «' . $calleNombre . '» no está registrada en la junta (créela antes de importar)';
            } elseif ($calleNombre === '') {
                $warnings[] = 'Sin calle: domicilio incompleto hasta activar';
            } elseif (($data['numero_casa'] ?? '') === '') {
                $warnings[] = 'Falta número de casa';
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }
}
