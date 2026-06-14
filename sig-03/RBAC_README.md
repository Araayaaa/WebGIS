# RBAC Implementation for sig-03

## Overview
Role-Based Access Control (RBAC) with resource-based permissions has been implemented for the sig-03 project.

## Test Credentials
- **Admin:** `admin` / `Admin@123`
- **Surveyor:** `surveyor` / `Survey@123`
- **Viewer:** `viewer` / `View@123`

## Architecture

### Authentication
- Session-based login system (not client-side role switching)
- Login page: `login.html`
- Login endpoint: `login_api.php`
- Logout endpoint: `logout_api.php`
- Auth helper: `auth.php`

### Permissions System
**15 Resource-Based Permissions:**

**Houses (Rumah Miskin):**
- `view_houses` - View house data
- `create_houses` - Create new houses
- `edit_houses` - Edit existing houses
- `delete_houses` - Delete houses

**Religious Centers (Rumah Ibadah):**
- `view_centers` - View religious centers
- `create_centers` - Create new religious centers
- `edit_centers` - Edit existing religious centers
- `delete_centers` - Delete religious centers

**Reports (Laporan):**
- `view_reports` - View reports
- `create_reports` - Create/submit reports
- `edit_reports` - Edit reports
- `delete_reports` - Delete reports
- `change_report_status` - Change report status (baru/ditangani/selesai)

**Users:**
- `view_users` - View users
- `manage_users` - Manage users (create/edit/delete)

### Role Hierarchy

**Admin (role_id = 3)**
- All 15 permissions ✓

**Surveyor (role_id = 2)**
- Can view and create/edit all resources
- Cannot delete anything
- Cannot manage users
- Permissions: view_houses, create_houses, edit_houses, view_centers, create_centers, edit_centers, view_reports, create_reports, edit_reports, change_report_status

**Viewer (role_id = 1)**
- Can only view maps and submit reports
- Permissions: view_houses, view_centers, create_reports

## Database Tables

### `users`
```sql
id, username, password (bcrypt), role_id, created_at
```

### `roles`
```sql
id (1=viewer, 2=surveyor, 3=admin), role_name, description, created_at
```

### `permissions`
```sql
id, permission_key, description, resource, action, created_at
```

### `role_permissions`
```sql
role_id, permission_id (junction table)
```

## Protected Endpoints

All endpoints now require authentication and appropriate permissions:

| Endpoint | Permission Required | Method |
|----------|-------------------|--------|
| `login_api.php` | None | POST |
| `logout_api.php` | authenticate | POST |
| `check_auth.php` | authenticate | GET |
| `get_data.php` | view_houses, view_centers, view_reports | POST |
| `simpan_rumah.php` | create_houses or edit_houses | POST |
| `simpan_pusat.php` | create_centers or edit_centers | POST |
| `simpan_laporan.php` | create_reports | POST |
| `hapus_rumah.php` | delete_houses | POST |
| `hapus_pusat.php` | delete_centers | POST |
| `hapus_laporan.php` | delete_reports | POST |
| `update_status.php` | edit_houses | POST |
| `update_radius.php` | edit_centers | POST |
| `update_laporan.php` | change_report_status | POST |
| `reset.php` | delete_houses + delete_centers | POST |

## Deployment

### Local Setup
1. Ensure MySQL is running
2. Access: `http://localhost/sig-03/setup_auth.php`
3. Follow the setup wizard to initialize the database
4. Login with test credentials
5. App redirects to `login.html` if not authenticated

### Railway Deployment
1. RBAC initialization happens automatically on container startup
2. `init_rbac.php` is called by `entrypoint.sh`
3. Tables and test users are created on first deploy
4. Safe to restart containers (script is idempotent)

## Frontend Changes

- Removed client-side role switcher modal
- Added server-side authentication check on page load
- Added logout button in navbar
- UI dynamically updates based on server-side permissions
- `script.js` now calls `checkAuth()` to verify session

## Security Notes

- Passwords are hashed using bcrypt (PASSWORD_BCRYPT)
- All endpoints require authentication check via `requireLogin()`
- Permission checks via `requirePermission($key)` before action
- Permission cache in memory to reduce database queries
- CSRF protection can be added to POST endpoints if needed
- Always use HTTPS in production

## Future Enhancements

- Admin panel for user management
- Admin panel for permission management
- API endpoint to change role permissions
- Audit logging for sensitive operations
- Two-factor authentication (2FA)
- Password reset flow
- User activity logging
