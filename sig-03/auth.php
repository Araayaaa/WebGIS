<?php
session_start();
require_once 'koneksi.php';

global $permissionCache;
$permissionCache = [];

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role_id' => $_SESSION['role_id'],
        'role_name' => $_SESSION['role_name']
    ];
}

function requireLogin() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }
}

function requireRole($requiredRole) {
    requireLogin();
    $user = getCurrentUser();
    if ($user['role_name'] !== $requiredRole) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Insufficient role']);
        exit;
    }
}

function hasPermission($permissionKey) {
    global $conn, $permissionCache;

    if (!isLoggedIn()) return false;

    $roleId = $_SESSION['role_id'];
    $cacheKey = "{$roleId}:{$permissionKey}";

    if (isset($permissionCache[$cacheKey])) {
        return $permissionCache[$cacheKey];
    }

    $stmt = $conn->prepare("
        SELECT 1 FROM role_permissions rp
        JOIN permissions p ON rp.permission_id = p.id
        WHERE rp.role_id = ? AND p.permission_key = ?
    ");
    $stmt->bind_param('is', $roleId, $permissionKey);
    $stmt->execute();
    $result = $stmt->get_result();
    $hasIt = $result->num_rows > 0;
    $stmt->close();

    $permissionCache[$cacheKey] = $hasIt;
    return $hasIt;
}

function requirePermission($permissionKey) {
    requireLogin();
    if (!hasPermission($permissionKey)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Permission denied: ' . $permissionKey]);
        exit;
    }
}

function redirectToLogin() {
    header('Location: login.html');
    exit;
}

function logout() {
    session_destroy();
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Logged out']);
    exit;
}
?>
