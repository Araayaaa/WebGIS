<?php
require_once 'koneksi.php';

$id = intval($_POST['id'] ?? 0);
if ($id < 1) { echo json_encode(['success' => false, 'message' => 'ID tidak valid']); exit; }

if ($conn->query("DELETE FROM laporan WHERE id=$id")) {
    echo json_encode(['success' => true, 'id' => $id]);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
$conn->close();
