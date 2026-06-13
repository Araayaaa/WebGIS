<?php
/**
 * Helper geometri & respons JSON.
 *
 * Perhitungan dilakukan di sisi server agar otoritatif:
 *  - Panjang LineString  -> haversine (meter)
 *  - Luas / keliling Polygon -> rumus area geodesik bola (meter & m2)
 *
 * Koordinat mengikuti format GeoJSON: [longitude, latitude].
 */

const EARTH_RADIUS = 6378137.0; // radius WGS84 (meter)

/** Kirim respons JSON lalu hentikan eksekusi. */
function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Ambil & decode body JSON dari request. */
function read_json_body(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** Jarak haversine antar dua titik [lng, lat] dalam meter. */
function haversine(array $a, array $b): float
{
    $lon1 = deg2rad($a[0]);
    $lat1 = deg2rad($a[1]);
    $lon2 = deg2rad($b[0]);
    $lat2 = deg2rad($b[1]);

    $dLat = $lat2 - $lat1;
    $dLon = $lon2 - $lon1;

    $h = sin($dLat / 2) ** 2
        + cos($lat1) * cos($lat2) * sin($dLon / 2) ** 2;

    return 2 * EARTH_RADIUS * asin(min(1.0, sqrt($h)));
}

/**
 * Panjang total sebuah LineString (array of [lng, lat]) dalam meter.
 */
function line_length(array $coords): float
{
    $total = 0.0;
    $n = count($coords);
    for ($i = 1; $i < $n; $i++) {
        $total += haversine($coords[$i - 1], $coords[$i]);
    }
    return $total;
}

/**
 * Luas polygon geodesik (ring tunggal, array of [lng, lat]) dalam meter persegi.
 * Memakai pendekatan integral bola (mirip Google Maps computeSignedArea).
 */
function ring_area(array $ring): float
{
    $n = count($ring);
    if ($n < 3) {
        return 0.0;
    }
    $area = 0.0;
    for ($i = 0; $i < $n; $i++) {
        $p1 = $ring[$i];
        $p2 = $ring[($i + 1) % $n];
        $area += deg2rad($p2[0] - $p1[0])
            * (2 + sin(deg2rad($p1[1])) + sin(deg2rad($p2[1])));
    }
    $area = $area * EARTH_RADIUS * EARTH_RADIUS / 2.0;
    return abs($area);
}

/** Keliling polygon (ring tunggal) dalam meter. */
function ring_perimeter(array $ring): float
{
    if (count($ring) < 2) {
        return 0.0;
    }
    // Pastikan ring tertutup untuk perhitungan keliling.
    $closed = $ring;
    if ($closed[0] !== $closed[count($closed) - 1]) {
        $closed[] = $closed[0];
    }
    return line_length($closed);
}

/**
 * Validasi & ekstrak geometry GeoJSON.
 * Mengembalikan [type, coordinates] atau null bila tidak valid.
 */
function parse_geometry($geojson): ?array
{
    if (is_string($geojson)) {
        $geojson = json_decode($geojson, true);
    }
    if (!is_array($geojson) || empty($geojson['type']) || !isset($geojson['coordinates'])) {
        return null;
    }
    return [$geojson['type'], $geojson['coordinates']];
}
