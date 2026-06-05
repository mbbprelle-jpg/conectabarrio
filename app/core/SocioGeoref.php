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

    /**
     * Geocodifica una dirección chilena usando Nominatim (OpenStreetMap).
     *
     * @return array{latitud: float, longitud: float, link_google: string}|null
     */
    public static function geocodeAddress(string $calle, string $numero, string $comuna): ?array {
        $calle = trim($calle);
        $numero = trim($numero);
        $comuna = trim($comuna);
        if ($calle === '' || $numero === '' || $comuna === '') {
            return null;
        }

        $query = $numero . ' ' . $calle . ', ' . $comuna . ', Chile';
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

    /**
     * Completa georeferencia si hay calle y número pero faltan coordenadas.
     */
    public static function resolveForSocio(array $data, array $callesById, string $comuna): array {
        if (!empty($data['latitud']) && !empty($data['longitud'])) {
            $data['link_google'] = self::buildGoogleMapsLink($data['latitud'], $data['longitud']);
            return $data;
        }

        $calleId = $data['calle_id'] ?? null;
        $numero = trim((string)($data['numero_casa'] ?? ''));
        if (empty($calleId) || $numero === '' || $comuna === '') {
            return $data;
        }

        $calleNombre = $callesById[(int)$calleId] ?? '';
        if ($calleNombre === '') {
            return $data;
        }

        $georef = self::geocodeAddress($calleNombre, $numero, $comuna);
        if ($georef === null) {
            return $data;
        }

        $data['latitud'] = $georef['latitud'];
        $data['longitud'] = $georef['longitud'];
        $data['link_google'] = $georef['link_google'];
        return $data;
    }
}
