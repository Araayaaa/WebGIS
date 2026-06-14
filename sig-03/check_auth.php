<?php
header('Content-Type: application/json');
require_once 'auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$user = getCurrentUser();
http_response_code(200);
echo json_encode([
    'success' => true,
    'user' => [
        'id' => $user['user_id'],
        'username' => $user['username'],
        'role' => $user['role_name']
    ]
]);
?>
