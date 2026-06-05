<?php

class SocioGeoref {

    public static function buildGoogleMapsLink($lat, $lng): string {
        $lat = round((float)$lat, 7);
        $lng = round((float)$lng, 7);
        return mb_strtolower("https://www.google.com/maps?q={$lat},{$lng}", 'UTF-8');
    }

    public static function parseCoord($value): ?float {
        if ($value === '' || $value === null) {
            return null;
        }
        if (!is_numeric($value)) {
            return null;
        }
        $coord = (float)$value;
        if ($coord < -180 || $coord > 180) {
            return null;
        }
        return round($coord, 7);
    }

    public static function parseFromPost(array $post): array {
        $lat = self::parseCoord($post['latitud'] ?? '');
        $lng = self::parseCoord($post['longitud'] ?? '');
        $link = mb_strtolower(trim((string)($post['link_google'] ?? '')), 'UTF-8');

        if ($lat !== null && $lng !== null) {
            return [
                'latitud' => $lat,
                'longitud' => $lng,
                'link_google' => self::buildGoogleMapsLink($lat, $lng),
            ];
        }

        if ($link !== '') {
            return [
                'latitud' => $lat,
                'longitud' => $lng,
                'link_google' => $link,
            ];
        }

        return [
            'latitud' => null,
            'longitud' => null,
            'link_google' => null,
        ];
    }

    public static function cacheKey(string $prefix, string $query): string {
        return $prefix . ':' . md5(mb_strtolower(trim($query), 'UTF-8'));
    }

    public static function getCached(Database $db, string $cacheKey): ?array {
        try {
            $db->query('SELECT latitud, longitud, link_google FROM georef_cache WHERE cache_key = :key LIMIT 1');
            $db->bind(':key', $cacheKey);
            $row = $db->single();
            if (!$row) {
                return null;
            }
            return [
                'latitud' => (float)$row->latitud,
                'longitud' => (float)$row->longitud,
                'link_google' => $row->link_google,
            ];
        } catch (Exception $e) {
            return null;
        }
    }

    public static function putCache(Database $db, string $cacheKey, array $georef): void {
        try {
            $db->query('INSERT INTO georef_cache (cache_key, latitud, longitud, link_google)
                VALUES (:key, :lat, :lng, :link)
                ON DUPLICATE KEY UPDATE latitud = VALUES(latitud), longitud = VALUES(longitud), link_google = VALUES(link_google)');
            $db->bind(':key', $cacheKey);
            $db->bind(':lat', $georef['latitud']);
            $db->bind(':lng', $georef['longitud']);
            $db->bind(':link', $georef['link_google']);
            $db->execute();
        } catch (Exception $e) {
            // Tabla opcional
        }
    }

    private static function nominatimSearch(string $query): ?array {
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
            'q' => $query,
            'format' => 'json',
            'limit' => 1,
            'countrycodes' => 'cl',
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: ConectaBarrio/1.0 (socio-georef)\r\nAccept: application/json\r\n",
                'timeout' => 8,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return null;
        }

        $results = json_decode($response, true);
        if (!is_array($results) || empty($results[0]['lat']) || empty($results[0]['lon'])) {
            return null;
        }

        $lat = self::parseCoord($results[0]['lat']);
        $lng = self::parseCoord($results[0]['lon']);
        if ($lat === null || $lng === null) {
            return null;
        }

        return [
            'latitud' => $lat,
            'longitud' => $lng,
            'link_google' => self::buildGoogleMapsLink($lat, $lng),
        ];
    }

    public static function geocodeQuery(string $query, string $cachePrefix = 'q', ?Database $db = null): ?array {
        $query = trim($query);
        if ($query === '') {
            return null;
        }
        if ($db) {
            $cached = self::getCached($db, self::cacheKey($cachePrefix, $query));
            if ($cached) {
                return $cached;
            }
        }
        $result = self::nominatimSearch($query);
        if ($result && $db) {
            self::putCache($db, self::cacheKey($cachePrefix, $query), $result);
        }
        return $result;
    }

    public static function geocodeStreet(string $calle, string $comuna, ?Database $db = null): ?array {
        $calle = trim($calle);
        $comuna = trim($comuna);
        if ($calle === '' || $comuna === '') {
            return null;
        }
        return self::geocodeQuery($calle . ', ' . $comuna . ', Chile', 'street', $db);
    }

    public static function geocodeAddress(string $calle, string $numero, string $comuna, ?Database $db = null): ?array {
        $calle = trim($calle);
        $numero = trim($numero);
        $comuna = trim($comuna);
        if ($calle === '' || $numero === '' || $comuna === '') {
            return null;
        }
        return self::geocodeQuery($numero . ' ' . $calle . ', ' . $comuna . ', Chile', 'addr', $db);
    }

    public static function geocodeFreeText(string $direccion, string $comuna, ?Database $db = null): ?array {
        $direccion = trim($direccion);
        $comuna = trim($comuna);
        if ($direccion === '') {
            return null;
        }
        $query = $comuna !== '' ? ($direccion . ', ' . $comuna . ', Chile') : ($direccion . ', Chile');
        return self::geocodeQuery($query, 'free', $db);
    }

    public static function resolveForMembresia(array $data, array $callesById, string $comuna, ?Database $db = null): array {
        if (!empty($data['latitud']) && !empty($data['longitud'])) {
            $data['link_google'] = self::buildGoogleMapsLink($data['latitud'], $data['longitud']);
            return $data;
        }

        $direccionTexto = trim((string)($data['direccion_texto'] ?? ''));
        if ($direccionTexto !== '') {
            $georef = self::geocodeFreeText($direccionTexto, $comuna, $db);
            if ($georef) {
                $data['latitud'] = $georef['latitud'];
                $data['longitud'] = $georef['longitud'];
                $data['link_google'] = $georef['link_google'];
            }
            return $data;
        }

        $calleId = $data['calle_id'] ?? null;
        $numero = trim((string)($data['numero_casa'] ?? ''));
        if (empty($calleId) || $numero === '' || $comuna === '') {
            return $data;
        }

        $calleNombre = $callesById[(int)$calleId]['nombre'] ?? ($callesById[(int)$calleId] ?? '');
        if ($calleNombre === '') {
            return $data;
        }

        $latCentro = $callesById[(int)$calleId]['lat_centro'] ?? null;
        $lngCentro = $callesById[(int)$calleId]['lng_centro'] ?? null;

        $georef = self::geocodeAddress($calleNombre, $numero, $comuna, $db);
        if ($georef === null && $latCentro !== null && $lngCentro !== null) {
            $georef = [
                'latitud' => self::parseCoord($latCentro),
                'longitud' => self::parseCoord($lngCentro),
                'link_google' => self::buildGoogleMapsLink($latCentro, $lngCentro),
            ];
        }

        if ($georef === null) {
            return $data;
        }

        $data['latitud'] = $georef['latitud'];
        $data['longitud'] = $georef['longitud'];
        $data['link_google'] = $georef['link_google'];
        return $data;
    }

    /** @deprecated Use resolveForMembresia */
    public static function resolveForSocio(array $data, array $callesById, string $comuna): array {
        $normalized = [];
        foreach ($callesById as $id => $val) {
            if (is_array($val)) {
                $normalized[$id] = $val;
            } else {
                $normalized[$id] = ['nombre' => $val, 'lat_centro' => null, 'lng_centro' => null];
            }
        }
        return self::resolveForMembresia($data, $normalized, $comuna);
    }
}
