<?php
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Hanya menerima POST']); exit;
}

$id     = intval($_POST['id']     ?? 0);
$status = $_POST['status']        ?? 'baru';

$valid = ['baru', 'ditangani', 'selesai'];
if (!in_array($status, $valid) || $id < 1) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']); exit;
}

$stmt = $conn->prepare("UPDATE laporan SET status=? WHERE id=?");
$stmt->bind_param('si', $status, $id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'id' => $id, 'status' => $status]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}
$stmt->close();
$conn->close();
