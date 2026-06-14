<?php
header('Content-Type: application/json');
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

session_destroy();
http_response_code(200);
echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
?>
