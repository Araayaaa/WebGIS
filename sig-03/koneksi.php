<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$host     = getenv('DB_HOST')    ?: 'localhost';
$user     = getenv('DB_USER')    ?: 'root';
$password = getenv('DB_PASS')    ?: '';
$database = getenv('DB_NAME_03') ?: 'sig_bansos';

// Connect without database first so we can create it if missing
$conn = new mysqli($host, $user, $password);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

$conn->set_charset("utf8mb4");

// Create database if it doesn't exist, then select it
$conn->query("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
if (!$conn->select_db($database)) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error' => 'Could not select database: ' . $conn->error
    ]));
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>
