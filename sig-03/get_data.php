<?php
require_once 'koneksi.php';

$response = ['success' => true, 'centers' => [], 'houses' => [], 'reports' => []];

$result = $conn->query("SELECT * FROM religious_centers ORDER BY id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $response['centers'][] = [
            'id'        => (int)$row['id'],
            'name'      => $row['name'],
            'address'   => $row['address'],
            'kas'       => (float)($row['kas'] ?? 0),
            'latitude'  => (float)$row['latitude'],
            'longitude' => (float)$row['longitude'],
            'radius'    => (int)$row['radius']
        ];
    }
    $result->free();
}

$result = $conn->query("SELECT * FROM houses ORDER BY id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $anggota = [];
        if (!empty($row['anggota'])) {
            $decoded = json_decode($row['anggota'], true);
            if (is_array($decoded)) $anggota = $decoded;
        }
        $response['houses'][] = [
            'id'             => (int)$row['id'],
            'latitude'       => (float)$row['latitude'],
            'longitude'      => (float)$row['longitude'],
            'address'        => $row['address'] ?? '',
            'rt'             => $row['rt'] ?? '',
            'rw'             => $row['rw'] ?? '',
            'kelurahan'      => $row['kelurahan'] ?? '',
            'status_miskin'  => $row['status_miskin'] ?? '',
            'jumlah_anggota' => (int)($row['jumlah_anggota'] ?? 0),
            'anggota'        => $anggota,
            'aid_status'     => $row['aid_status'] ?? 'outside',
            'has_data'       => (int)($row['has_data'] ?? 0)
        ];
    }
    $result->free();
}

$result = $conn->query("SELECT * FROM laporan ORDER BY created_at DESC LIMIT 100");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $response['reports'][] = [
            'id'        => (int)$row['id'],
            'name'      => $row['pelapor'] ?? 'Anonim',
            'text'      => $row['deskripsi'],
            'lokasi'    => $row['lokasi'] ?? '',
            'imgBase64' => $row['foto_base64'] ?? null,
            'status'    => $row['status'] ?? 'baru',
            'time'      => date('d/m/Y H:i', strtotime($row['created_at']))
        ];
    }
    $result->free();
}

echo json_encode($response);
$conn->close();
