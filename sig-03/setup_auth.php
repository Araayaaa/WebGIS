<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup Auth — BantSOSial GIS</title>
    <style>
        body { font-family: monospace; background: #f5f5f5; padding: 20px; }
        .setup-log { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .log-entry { padding: 8px; margin: 4px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
        .info { background: #d1ecf1; color: #0c5460; border-left: 4px solid #17a2b8; }
    </style>
</head>
<body>

<div class="setup-log">
    <h2>🔧 BantSOSial Auth System Setup</h2>
    <div id="log"></div>
</div>

<script>
const log = document.getElementById('log');

function logEntry(msg, type = 'info') {
    const div = document.createElement('div');
    div.className = `log-entry ${type}`;
    div.textContent = msg;
    log.appendChild(div);
    console.log(msg);
}

async function runSetup() {
    logEntry('Starting setup...', 'info');

    try {
        // Step 1: Create users table
        logEntry('Creating users table...', 'info');
        const res1 = await fetch('setup_create_users_table.php');
        const data1 = await res1.json();
        if (data1.success) {
            logEntry('✓ Users table created', 'success');
        } else {
            logEntry('✗ Error: ' + data1.error, 'error');
            return;
        }

        // Step 2: Load permission schema
        logEntry('Loading permission schema...', 'info');
        const res2 = await fetch('setup_load_permissions.php');
        const data2 = await res2.json();
        if (data2.success) {
            logEntry('✓ Permission schema loaded', 'success');
        } else {
            logEntry('✗ Error: ' + data2.error, 'error');
            return;
        }

        // Step 3: Seed users
        logEntry('Seeding test users...', 'info');
        const res3 = await fetch('setup_seed_users.php');
        const data3 = await res3.json();
        if (data3.success) {
            logEntry('✓ Test users created:', 'success');
            logEntry('  - admin / Admin@123', 'success');
            logEntry('  - surveyor / Survey@123', 'success');
            logEntry('  - viewer / View@123', 'success');
        } else {
            logEntry('✗ Error: ' + data3.error, 'error');
            return;
        }

        logEntry('Setup complete! You can now login.', 'success');
        logEntry('Redirecting to login page...', 'info');
        setTimeout(() => {
            window.location.href = 'login.html';
        }, 2000);
    } catch (err) {
        logEntry('✗ Error: ' + err.message, 'error');
    }
}

runSetup();
</script>

</body>
</html>
