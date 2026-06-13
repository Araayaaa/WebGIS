<?php
/**
 * Handler CRUD generik untuk fitur peta (tanah / jalan).
 *
 * $cfg = [
 *   'table'    => nama tabel,
 *   'fields'   => daftar kolom teks yang boleh diisi user (selain geometri/ukuran),
 *   'measure'  => fungsi(array $geometry): array ukuran-ukuran yang dihitung server,
 *   'geomType' => 'Polygon' | 'LineString' (untuk validasi),
 * ]
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/geo.php';

function handle_crud(array $cfg): void
{
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');

    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'OPTIONS') {
        json_response(['ok' => true]);
    }

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

    try {
        switch ($method) {
            case 'GET':
                $id ? crud_show($cfg, $id) : crud_list($cfg);
                break;
            case 'POST':
                crud_create($cfg);
                break;
            case 'PUT':
                crud_update($cfg, $id);
                break;
            case 'DELETE':
                crud_delete($cfg, $id);
                break;
            default:
                json_response(['error' => 'Method tidak didukung'], 405);
        }
    } catch (Throwable $e) {
        json_response(['error' => 'Server error: ' . $e->getMessage()], 500);
    }
}

function crud_list(array $cfg): void
{
    $rows = db()->query("SELECT * FROM {$cfg['table']} ORDER BY id DESC")->fetchAll();
    foreach ($rows as &$r) {
        $r['geojson'] = json_decode($r['geojson'], true);
    }
    json_response(['data' => $rows]);
}

function crud_show(array $cfg, int $id): void
{
    $stmt = db()->prepare("SELECT * FROM {$cfg['table']} WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        json_response(['error' => 'Data tidak ditemukan'], 404);
    }
    $row['geojson'] = json_decode($row['geojson'], true);
    json_response(['data' => $row]);
}

/** Validasi input & hitung ukuran. Mengembalikan [values, geojsonString]. */
function crud_prepare(array $cfg, array $body): array
{
    $geom = parse_geometry($body['geojson'] ?? null);
    if ($geom === null) {
        json_response(['error' => 'Geometri (geojson) tidak valid'], 422);
    }
    [$type, $coords] = $geom;
    if ($type !== $cfg['geomType']) {
        json_response(['error' => "Geometri harus bertipe {$cfg['geomType']}, diterima {$type}"], 422);
    }

    $values = [];
    foreach ($cfg['fields'] as $f) {
        $values[$f] = isset($body[$f]) ? trim((string) $body[$f]) : null;
    }
    if (empty($values['nama'])) {
        json_response(['error' => 'Nama wajib diisi'], 422);
    }

    // Warna default bila kosong.
    if (array_key_exists('warna', $values) && empty($values['warna'])) {
        $values['warna'] = $cfg['geomType'] === 'Polygon' ? '#22c55e' : '#ef4444';
    }

    // Ukuran dihitung server (otoritatif).
    $measures = $cfg['measure']($geom);

    $geojsonStr = json_encode(['type' => $type, 'coordinates' => $coords], JSON_UNESCAPED_UNICODE);

    return [array_merge($values, $measures), $geojsonStr];
}

function crud_create(array $cfg): void
{
    [$values, $geojsonStr] = crud_prepare($cfg, read_json_body());

    $cols = array_keys($values);
    $cols[] = 'geojson';
    $placeholders = implode(', ', array_fill(0, count($cols), '?'));
    $colList = implode(', ', $cols);

    $params = array_values($values);
    $params[] = $geojsonStr;

    $stmt = db()->prepare("INSERT INTO {$cfg['table']} ($colList) VALUES ($placeholders)");
    $stmt->execute($params);

    crud_show($cfg, (int) db()->lastInsertId());
}

function crud_update(array $cfg, int $id): void
{
    if (!$id) {
        json_response(['error' => 'ID tidak valid'], 400);
    }
    [$values, $geojsonStr] = crud_prepare($cfg, read_json_body());

    $sets = [];
    foreach (array_keys($values) as $c) {
        $sets[] = "$c = ?";
    }
    $sets[] = 'geojson = ?';

    $params = array_values($values);
    $params[] = $geojsonStr;
    $params[] = $id;

    $stmt = db()->prepare("UPDATE {$cfg['table']} SET " . implode(', ', $sets) . " WHERE id = ?");
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        // Tetap kembalikan data terkini (mungkin tidak ada perubahan nilai).
        $check = db()->prepare("SELECT id FROM {$cfg['table']} WHERE id = ?");
        $check->execute([$id]);
        if (!$check->fetch()) {
            json_response(['error' => 'Data tidak ditemukan'], 404);
        }
    }
    crud_show($cfg, $id);
}

function crud_delete(array $cfg, int $id): void
{
    if (!$id) {
        json_response(['error' => 'ID tidak valid'], 400);
    }
    $stmt = db()->prepare("DELETE FROM {$cfg['table']} WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() === 0) {
        json_response(['error' => 'Data tidak ditemukan'], 404);
    }
    json_response(['ok' => true, 'deleted' => $id]);
}
