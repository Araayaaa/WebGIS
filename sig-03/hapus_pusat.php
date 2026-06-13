<?php
require_once 'koneksi.php';

$id = intval($_POST['id'] ?? 0);
if ($id < 1) { echo json_encode(['success' => false, 'message' => 'ID tidak valid']); exit; }

$conn->query("DELETE FROM aid_logs WHERE religious_center_id=$id");

if ($conn->query("DELETE FROM religious_centers WHERE id=$id")) {
    echo json_encode(['success' => true, 'id' => $id]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
$conn->close();
