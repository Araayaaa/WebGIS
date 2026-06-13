<?php
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Hanya POST']); exit;
}

$name   = strip_tags(trim($_POST['name']   ?? 'Anonim'));
$text   = strip_tags(trim($_POST['text']   ?? ''));
$lokasi = strip_tags(trim($_POST['lokasi'] ?? ''));
$img    = $_POST['img'] ?? '';

if (empty($text)) {
    echo json_encode(['success' => false, 'message' => 'Deskripsi laporan tidak boleh kosong']); exit;
}
if (strlen($img) > 7_000_000) {
    echo json_encode(['success' => false, 'message' => 'Ukuran foto terlalu besar (max 5MB)']); exit;
}

$stmt = $conn->prepare("INSERT INTO laporan (pelapor, deskripsi, lokasi, foto_base64) VALUES (?, ?, ?, ?)");
$stmt->bind_param('ssss', $name, $text, $lokasi, $img);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $conn->insert_id]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
$stmt->close();
$conn->close();
