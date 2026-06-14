<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

session_start();
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Username dan password harus diisi']);
    exit;
}

$stmt = $conn->prepare("
    SELECT u.id, u.username, u.password, u.role_id, r.role_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.username = ?
");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Username atau password salah']);
    $stmt->close();
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

if (!password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Username atau password salah']);
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role_id'] = $user['role_id'];
$_SESSION['role_name'] = $user['role_name'];

http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Login berhasil',
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role_name']
    ]
]);
exit;
?>
