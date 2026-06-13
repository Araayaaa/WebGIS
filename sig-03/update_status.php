<?php
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Hanya menerima POST']); exit;
}

$houseId  = intval($_POST['house_id']  ?? 0);
$aidStatus = $_POST['aid_status']      ?? 'outside';
$centerId = intval($_POST['center_id'] ?? 0);

$validAid = ['helped', 'not_helped', 'outside'];
if (!in_array($aidStatus, $validAid)) {
    echo json_encode(['success' => false, 'message' => 'Status tidak valid']); exit;
}
if ($houseId < 1) {
    echo json_encode(['success' => false, 'message' => 'house_id tidak valid']); exit;
}

$stmt = $conn->prepare("UPDATE houses SET aid_status=?, updated_at=NOW() WHERE id=?");
$stmt->bind_param('si', $aidStatus, $houseId);

if ($stmt->execute()) {
    $stmt->close();
    if ($aidStatus === 'helped' && $centerId > 0) {
        $logStatus = 'helped';
        $ls = $conn->prepare("INSERT INTO aid_logs (house_id, religious_center_id, status) VALUES (?, ?, ?)");
        $ls->bind_param('iis', $houseId, $centerId, $logStatus);
        $ls->execute(); $ls->close();
    } elseif ($aidStatus !== 'helped') {
        $logStatus = 'reverted';
        $ls = $conn->prepare("INSERT INTO aid_logs (house_id, religious_center_id, status) VALUES (?, ?, ?)");
        $ls->bind_param('iis', $houseId, $centerId, $logStatus);
        $ls->execute(); $ls->close();
    }
    echo json_encode(['success' => true, 'house_id' => $houseId, 'aid_status' => $aidStatus]);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
    $stmt->close();
}
$conn->close();
