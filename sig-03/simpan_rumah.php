<?php
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Hanya POST']); exit;
}

$id            = intval($_POST['id']             ?? 0);
$lat           = floatval($_POST['lat']          ?? 0);
$lng           = floatval($_POST['lng']          ?? 0);
$address       = $conn->real_escape_string(strip_tags(trim($_POST['address']      ?? '')));
$rt            = $conn->real_escape_string(strip_tags(trim($_POST['rt']           ?? '')));
$rw            = $conn->real_escape_string(strip_tags(trim($_POST['rw']           ?? '')));
$kelurahan     = $conn->real_escape_string(strip_tags(trim($_POST['kelurahan']    ?? '')));
$statusMiskin  = $_POST['status_miskin']  ?? '';
$jumlahAnggota = intval($_POST['jumlah_anggota'] ?? 0);
$anggotaRaw    = $_POST['anggota']        ?? '[]';
$aidStatus     = $_POST['aid_status']     ?? 'outside';
$hasData       = intval($_POST['has_data'] ?? 0);

$validStatuses = ['sangat_miskin', 'miskin', 'tidak_miskin', ''];
if (!in_array($statusMiskin, $validStatuses)) $statusMiskin = '';
$validAid = ['helped', 'not_helped', 'outside'];
if (!in_array($aidStatus, $validAid)) $aidStatus = 'outside';

$anggotaDecoded = json_decode($anggotaRaw, true);
if (!is_array($anggotaDecoded)) $anggotaDecoded = [];
$anggotaJson = json_encode($anggotaDecoded, JSON_UNESCAPED_UNICODE);

if ($id > 0) {
    $stmt = $conn->prepare("UPDATE houses SET latitude=?, longitude=?, address=?, rt=?, rw=?, kelurahan=?, status_miskin=?, jumlah_anggota=?, anggota=?, aid_status=?, has_data=?, updated_at=NOW() WHERE id=?");
    $stmt->bind_param('ddsssssiisii', $lat, $lng, $address, $rt, $rw, $kelurahan, $statusMiskin, $jumlahAnggota, $anggotaJson, $aidStatus, $hasData, $id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $id, 'action' => 'updated']);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
    $stmt->close();
} else {
    $stmt = $conn->prepare("INSERT INTO houses (latitude, longitude, address, rt, rw, kelurahan, status_miskin, jumlah_anggota, anggota, aid_status, has_data) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ddsssssissi', $lat, $lng, $address, $rt, $rw, $kelurahan, $statusMiskin, $jumlahAnggota, $anggotaJson, $aidStatus, $hasData);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'id' => $conn->insert_id, 'action' => 'inserted']);
    } else {
        echo json_encode(['success' => false, 'message' => $stmt->error]);
    }
    $stmt->close();
}
$conn->close();
