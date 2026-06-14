<?php
header('Content-Type: application/json');
require_once 'koneksi.php';

try {
    // Create roles table
    $conn->query("
        CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role_name VARCHAR(50) UNIQUE NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Create permissions table
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

    // Create role_permissions junction table
    $conn->query("
        CREATE TABLE IF NOT EXISTS role_permissions (
            role_id INT NOT NULL,
            permission_id INT NOT NULL,
            PRIMARY KEY (role_id, permission_id),
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Insert roles
    $conn->query("INSERT IGNORE INTO roles (id, role_name, description) VALUES
        (1, 'viewer', 'Viewer - can only view data and submit reports'),
        (2, 'surveyor', 'Surveyor - can view and create/edit data, cannot delete'),
        (3, 'admin', 'Admin - full access to all resources')
    ");

    // Insert permissions
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

    // Assign permissions to Viewer
    $conn->query("INSERT IGNORE INTO role_permissions (role_id, permission_id)
        SELECT 1, id FROM permissions WHERE permission_key IN ('view_houses', 'view_centers', 'create_reports')
    ");

    // Assign permissions to Surveyor
    $conn->query("INSERT IGNORE INTO role_permissions (role_id, permission_id)
        SELECT 2, id FROM permissions WHERE permission_key IN (
            'view_houses', 'create_houses', 'edit_houses',
            'view_centers', 'create_centers', 'edit_centers',
            'view_reports', 'create_reports', 'edit_reports', 'change_report_status'
        )
    ");

    // Assign all permissions to Admin
    $conn->query("INSERT IGNORE INTO role_permissions (role_id, permission_id)
        SELECT 3, id FROM permissions
    ");

    echo json_encode(['success' => true, 'message' => 'Permission schema loaded']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
$conn->close();
?>
