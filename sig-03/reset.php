<?php
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Hanya menerima POST']); exit;
}

$conn->query("SET FOREIGN_KEY_CHECKS=0");
$conn->query("TRUNCATE TABLE aid_logs");
$conn->query("TRUNCATE TABLE houses");
$conn->query("TRUNCATE TABLE religious_centers");
$conn->query("TRUNCATE TABLE laporan");
$conn->query("SET FOREIGN_KEY_CHECKS=1");

echo json_encode(['success' => true, 'message' => 'Semua data berhasil direset']);
$conn->close();
