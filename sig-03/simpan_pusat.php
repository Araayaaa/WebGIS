<?php
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Hanya menerima POST']); exit;
}

$id      = intval($_POST['id']      ?? 0);
$name    = $conn->real_escape_string(strip_tags(trim($_POST['name']    ?? '')));
$address = $conn->real_escape_string(strip_tags(trim($_POST['address'] ?? '')));
$kas     = floatval($_POST['kas']   ?? 0);
$lat     = floatval($_POST['lat']   ?? 0);
$lng     = floatval($_POST['lng']   ?? 0);
$radius  = intval($_POST['radius']  ?? 300);
$isUpd   = isset($_POST['update']) && $_POST['update'] === '1';

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Nama wajib diisi']); exit;
}
if ($radius < 50) $radius = 50;

if ($id > 0 || $isUpd) {
    $stmt = $conn->prepare("UPDATE religious_centers SET name=?, address=?, kas=?, latitude=?, longitude=?, radius=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param('ssdddii', $name, $address, $kas, $lat, $lng, $radius, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $id, 'action' => 'updated']);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
    $stmt->close();
} else {
    $stmt = $conn->prepare("INSERT INTO religious_centers (name, address, kas, latitude, longitude, radius) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssdddi', $name, $address, $kas, $lat, $lng, $radius);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id, 'action' => 'inserted']);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
    $stmt->close();
}
$conn->close();
