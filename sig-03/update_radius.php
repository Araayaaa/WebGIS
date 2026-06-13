<?php
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Hanya menerima POST']); exit;
}

$id     = intval($_POST['id']     ?? 0);
$radius = intval($_POST['radius'] ?? 300);

if ($id < 1) { echo json_encode(['success' => false, 'message' => 'ID tidak valid']); exit; }
if ($radius < 50) $radius = 50;

$stmt = $conn->prepare("UPDATE religious_centers SET radius=?, updated_at=NOW() WHERE id=?");
$stmt->bind_param('ii', $radius, $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $id, 'radius' => $radius]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
$stmt->close();
$conn->close();
