<?php
header('Content-Type: application/json');
require_once 'koneksi.php';

try {
    $users = [
        ['admin', 'Admin@123', 3],
        ['surveyor', 'Survey@123', 2],
        ['viewer', 'View@123', 1]
    ];

    foreach ($users as [$username, $password, $roleId]) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT IGNORE INTO users (username, password, role_id) VALUES (?, ?, ?)");
        $stmt->bind_param('ssi', $username, $hashedPassword, $roleId);
        $stmt->execute();
        $stmt->close();
    }

    echo json_encode(['success' => true, 'message' => 'Users seeded']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
$conn->close();
?>
