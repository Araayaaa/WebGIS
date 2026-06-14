#!/usr/bin/env php
<?php
// Initialize RBAC system - safe to run multiple times
// Called by entrypoint to set up database on first deploy

$host     = getenv('DB_HOST')     ?: 'localhost';
$user     = getenv('DB_USER')     ?: 'root';
$password = getenv('DB_PASS')     ?: '';
$database = getenv('DB_NAME_03')  ?: 'sig_bansos';

// Retry connection up to 10 times (DB may not be ready immediately)
$conn = null;
for ($i = 1; $i <= 10; $i++) {
    $conn = new mysqli($host, $user, $password, $database);
    if (!$conn->connect_error) break;
    echo "[RBAC] DB not ready (attempt $i/10): {$conn->connect_error}\n";
    sleep(3);
}

if ($conn->connect_error) {
    echo "[RBAC] Failed to connect after 10 attempts: {$conn->connect_error}\n";
    exit(1);
}

echo "[RBAC] Connected to database.\n";

$conn->set_charset("utf8mb4");

try {
    echo "[RBAC] Creating roles table...\n";
    $conn->query("
        CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role_name VARCHAR(50) UNIQUE NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    echo "[RBAC] Creating permissions table...\n";
    $conn->query("
        CREATE TABLE IF NOT EXISTS permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            permission_key VARCHAR(100) UNIQUE NOT NULL,
            description TEXT,
            resource VARCHAR(50),
            action VARCHAR(50),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    echo "[RBAC] Creating role_permissions table...\n";
    $conn->query("
        CREATE TABLE IF NOT EXISTS role_permissions (
            role_id INT NOT NULL,
            permission_id INT NOT NULL,
            PRIMARY KEY (role_id, permission_id),
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    echo "[RBAC] Creating users table...\n";
    $conn->query("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (role_id) REFERENCES roles(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    echo "[RBAC] Seeding roles...\n";
    $conn->query("INSERT IGNORE INTO roles (id, role_name, description) VALUES
        (1, 'viewer', 'Viewer - can only view data and submit reports'),
        (2, 'surveyor', 'Surveyor - can view and create/edit data, cannot delete'),
        (3, 'admin', 'Admin - full access to all resources')
    ");

    echo "[RBAC] Seeding permissions...\n";
    $conn->query("INSERT IGNORE INTO permissions (permission_key, description, resource, action) VALUES
        ('view_houses', 'View house data', 'houses', 'view'),
        ('create_houses', 'Create new houses', 'houses', 'create'),
        ('edit_houses', 'Edit existing houses', 'houses', 'edit'),
        ('delete_houses', 'Delete houses', 'houses', 'delete'),
        ('view_centers', 'View religious centers', 'centers', 'view'),
        ('create_centers', 'Create new religious centers', 'centers', 'create'),
        ('edit_centers', 'Edit existing religious centers', 'centers', 'edit'),
        ('delete_centers', 'Delete religious centers', 'centers', 'delete'),
        ('view_reports', 'View reports', 'reports', 'view'),
        ('create_reports', 'Create/submit reports', 'reports', 'create'),
        ('edit_reports', 'Edit reports', 'reports', 'edit'),
        ('delete_reports', 'Delete reports', 'reports', 'delete'),
        ('change_report_status', 'Change report status', 'reports', 'change_status'),
        ('view_users', 'View users', 'users', 'view'),
        ('manage_users', 'Manage users (create/edit/delete)', 'users', 'manage')
    ");

    echo "[RBAC] Assigning permissions to Viewer...\n";
    $conn->query("INSERT IGNORE INTO role_permissions (role_id, permission_id)
        SELECT 1, id FROM permissions WHERE permission_key IN (
            'view_houses', 'view_centers', 'create_reports'
        )
    ");

    echo "[RBAC] Assigning permissions to Surveyor...\n";
    $conn->query("INSERT IGNORE INTO role_permissions (role_id, permission_id)
        SELECT 2, id FROM permissions WHERE permission_key IN (
            'view_houses', 'create_houses', 'edit_houses',
            'view_centers', 'create_centers', 'edit_centers',
            'view_reports', 'create_reports', 'edit_reports', 'change_report_status'
        )
    ");

    echo "[RBAC] Assigning all permissions to Admin...\n";
    $conn->query("INSERT IGNORE INTO role_permissions (role_id, permission_id)
        SELECT 3, id FROM permissions
    ");

    echo "[RBAC] Seeding users...\n";
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

    echo "[RBAC] ✓ RBAC initialization complete!\n";
    $conn->close();
    exit(0);

} catch (Exception $e) {
    echo "[RBAC] ✗ Error: {$e->getMessage()}\n";
    $conn->close();
    exit(1);
}
?>
