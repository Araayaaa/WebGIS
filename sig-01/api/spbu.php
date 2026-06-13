<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo    = getDB();

function respond(bool $success, $data = null, string $message = '', int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

// ── GET ─────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $filter = $_GET['filter'] ?? 'semua';

    $sql    = 'SELECT id, kode, nama, is_24jam, latitude, longitude, created_at FROM spbu';
    $params = [];

    if ($filter === '24jam') {
        $sql .= ' WHERE is_24jam = 1';
    } elseif ($filter === 'tidak') {
        $sql .= ' WHERE is_24jam = 0';
    }

    $sql .= ' ORDER BY nama ASC';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Cast types
    foreach ($rows as &$r) {
        $r['id']       = (int) $r['id'];
        $r['is_24jam'] = (bool) $r['is_24jam'];
        $r['latitude'] = (float) $r['latitude'];
        $r['longitude']= (float) $r['longitude'];
    }

    respond(true, $rows);
}

// ── POST (tambah) ─────────────────────────────────────────────────────────
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);

    $kode      = trim($body['kode']      ?? '');
    $nama      = trim($body['nama']      ?? '');
    $is_24jam  = isset($body['is_24jam']) ? (int)(bool)$body['is_24jam'] : 0;
    $latitude  = isset($body['latitude'])  ? (float)$body['latitude']  : null;
    $longitude = isset($body['longitude']) ? (float)$body['longitude'] : null;

    if (!$kode || !$nama || $latitude === null || $longitude === null) {
        respond(false, null, 'Data tidak lengkap', 400);
    }

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO spbu (kode, nama, is_24jam, latitude, longitude) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$kode, $nama, $is_24jam, $latitude, $longitude]);
        $id = (int) $pdo->lastInsertId();

        $stmt2 = $pdo->prepare('SELECT id, kode, nama, is_24jam, latitude, longitude FROM spbu WHERE id = ?');
        $stmt2->execute([$id]);
        $spbu = $stmt2->fetch();
        $spbu['id']       = (int) $spbu['id'];
        $spbu['is_24jam'] = (bool) $spbu['is_24jam'];
        $spbu['latitude'] = (float) $spbu['latitude'];
        $spbu['longitude']= (float) $spbu['longitude'];

        respond(true, $spbu, 'SPBU berhasil ditambahkan', 201);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            respond(false, null, 'Kode SPBU sudah digunakan', 409);
        }
        respond(false, null, 'Gagal menyimpan data', 500);
    }
}

// ── PUT (update) ─────────────────────────────────────────────────────────
if ($method === 'PUT') {
    $body = json_decode(file_get_contents('php://input'), true);

    $id        = isset($body['id'])        ? (int)$body['id']        : 0;
    $kode      = isset($body['kode'])      ? trim($body['kode'])      : null;
    $nama      = isset($body['nama'])      ? trim($body['nama'])      : null;
    $is_24jam  = isset($body['is_24jam'])  ? (int)(bool)$body['is_24jam'] : null;
    $latitude  = isset($body['latitude'])  ? (float)$body['latitude']  : null;
    $longitude = isset($body['longitude']) ? (float)$body['longitude'] : null;

    if (!$id) { respond(false, null, 'ID tidak valid', 400); }

    // Build dynamic SET clause
    $sets   = [];
    $params = [];

    if ($kode      !== null) { $sets[] = 'kode = ?';      $params[] = $kode; }
    if ($nama      !== null) { $sets[] = 'nama = ?';      $params[] = $nama; }
    if ($is_24jam  !== null) { $sets[] = 'is_24jam = ?';  $params[] = $is_24jam; }
    if ($latitude  !== null) { $sets[] = 'latitude = ?';  $params[] = $latitude; }
    if ($longitude !== null) { $sets[] = 'longitude = ?'; $params[] = $longitude; }

    if (empty($sets)) { respond(false, null, 'Tidak ada data yang diubah', 400); }

    $params[] = $id;
    try {
        $stmt = $pdo->prepare('UPDATE spbu SET ' . implode(', ', $sets) . ' WHERE id = ?');
        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {
            respond(false, null, 'SPBU tidak ditemukan', 404);
        }

        $stmt2 = $pdo->prepare('SELECT id, kode, nama, is_24jam, latitude, longitude FROM spbu WHERE id = ?');
        $stmt2->execute([$id]);
        $spbu = $stmt2->fetch();
        $spbu['id']       = (int) $spbu['id'];
        $spbu['is_24jam'] = (bool) $spbu['is_24jam'];
        $spbu['latitude'] = (float) $spbu['latitude'];
        $spbu['longitude']= (float) $spbu['longitude'];

        respond(true, $spbu, 'SPBU berhasil diperbarui');
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            respond(false, null, 'Kode SPBU sudah digunakan', 409);
        }
        respond(false, null, 'Gagal memperbarui data', 500);
    }
}

// ── DELETE ────────────────────────────────────────────────────────────────
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if (!$id) { respond(false, null, 'ID tidak valid', 400); }

    $stmt = $pdo->prepare('DELETE FROM spbu WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        respond(false, null, 'SPBU tidak ditemukan', 404);
    }
    respond(true, null, 'SPBU berhasil dihapus');
}

respond(false, null, 'Method tidak diizinkan', 405);
