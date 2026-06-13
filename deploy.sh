#!/bin/bash

#############################################################################
# 🚀 FULLSTACK SIG - RAILWAY DEPLOYMENT AUTOMATION
# 
# This script automates the entire deployment process:
# 1. Updates koneksi.php files for environment variables
# 2. Creates necessary config files (.env.example, Dockerfile, railway.json)
# 3. Commits and pushes to GitHub
# 4. Guides through Railway setup
#
# Usage: bash deploy.sh
#############################################################################

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Helper functions
print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if we're in the right directory
if [ ! -f "index.php" ] || [ ! -d "sig-01" ] || [ ! -d "sig-03" ]; then
    print_error "This script must be run from the fullstack-sig root directory"
    exit 1
fi

print_header "🚀 FULLSTACK SIG - RAILWAY DEPLOYMENT"

# Step 1: Backup existing koneksi files
print_header "Step 1: Backup Existing Files"

for project in sig-01 sig-02 sig-03; do
    if [ -f "$project/koneksi.php" ]; then
        cp "$project/koneksi.php" "$project/koneksi.php.backup.$(date +%s)"
        print_success "Backed up $project/koneksi.php"
    fi
done

# Step 2: Create .gitignore
print_header "Step 2: Create .gitignore"

cat > .gitignore << 'EOF'
.env
.env.local
.env.*.local
*.log
node_modules/
.DS_Store
Thumbs.db
.vscode/
.idea/
*.swp
*.swo
*~
.cache/
*.backup.*
EOF

print_success ".gitignore created"

# Step 3: Create .env.example
print_header "Step 3: Create .env.example"

cat > .env.example << 'EOF'
# Database Configuration
# Railway MySQL service will provide these automatically
DB_HOST=localhost
DB_USER=root
DB_PASS=your_password_here

# Database Names (one per project)
DB_NAME=sig_spbu
DB_NAME_02=sig_tanah_jalan
DB_NAME_03=sig_bansos

# Application Settings
APP_ENV=production
APP_DEBUG=false
APP_PORT=80

# PHP Settings
PHP_MEMORY_LIMIT=256M
PHP_POST_MAX_SIZE=50M
PHP_UPLOAD_MAX_FILESIZE=50M
EOF

print_success ".env.example created"

# Step 4: Create Dockerfile
print_header "Step 4: Create Dockerfile"

cat > Dockerfile << 'EOF'
# Use official PHP 8.2 with Apache
FROM php:8.2-apache

# Install MySQL/MariaDB extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . .

# Fix file permissions
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Create directories for uploads/logs
RUN mkdir -p /var/www/html/uploads /var/log/apache2 && \
    chown -R www-data:www-data /var/www/html/uploads

# Environment variables
ENV PHP_MEMORY_LIMIT=256M \
    PHP_POST_MAX_SIZE=50M \
    PHP_UPLOAD_MAX_FILESIZE=50M

# Configure Apache
RUN echo '<Directory /var/www/html>' > /etc/apache2/conf-available/app.conf && \
    echo '    RewriteEngine On' >> /etc/apache2/conf-available/app.conf && \
    echo '    RewriteBase /' >> /etc/apache2/conf-available/app.conf && \
    echo '    RewriteCond %{REQUEST_FILENAME} !-f' >> /etc/apache2/conf-available/app.conf && \
    echo '    RewriteCond %{REQUEST_FILENAME} !-d' >> /etc/apache2/conf-available/app.conf && \
    echo '    RewriteRule ^(.*)$ index.php [L]' >> /etc/apache2/conf-available/app.conf && \
    echo '</Directory>' >> /etc/apache2/conf-available/app.conf && \
    a2enconf app

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Start Apache
CMD ["apache2-foreground"]
EOF

print_success "Dockerfile created"

# Step 5: Create railway.json
print_header "Step 5: Create railway.json"

cat > railway.json << 'EOF'
{
  "build": {
    "builder": "dockerfile"
  },
  "deploy": {
    "numReplicas": 1,
    "startCommand": "apache2-foreground",
    "restartPolicyType": "on-failure",
    "restartPolicyMaxRetries": 3
  }
}
EOF

print_success "railway.json created"

# Step 6: Update koneksi.php files
print_header "Step 6: Update koneksi.php Files"

# SIG-01
cat > sig-01/koneksi.php << 'EOF'
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$database = getenv('DB_NAME') ?: 'sig_spbu';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

$conn->set_charset("utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>
EOF

print_success "sig-01/koneksi.php updated"

# SIG-02
cat > sig-02/koneksi.php << 'EOF'
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$database = getenv('DB_NAME_02') ?: 'sig_tanah_jalan';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

$conn->set_charset("utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>
EOF

print_success "sig-02/koneksi.php updated"

# SIG-03
cat > sig-03/koneksi.php << 'EOF'
<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: '';
$database = getenv('DB_NAME_03') ?: 'sig_bansos';

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode([
        'success' => false,
        'error' => 'Database connection failed: ' . $conn->connect_error
    ]));
}

$conn->set_charset("utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
?>
EOF

print_success "sig-03/koneksi.php updated"

# Step 7: Git operations
print_header "Step 7: Git Operations"

# Check if git is initialized
if [ ! -d ".git" ]; then
    print_warning "Git not initialized, initializing now..."
    git init
    git config user.name "Fullstack SIG Deployer"
    git config user.email "deploy@fullstack-sig.local"
fi

# Add all files
git add .

# Check if there are changes
if git diff --cached --quiet; then
    print_warning "No changes to commit"
else
    git commit -m "Prepare for Railway deployment: update DB connections and Docker config"
    print_success "Changes committed"
fi

# Step 8: Show summary
print_header "✅ AUTOMATION COMPLETE"

echo -e "${GREEN}Files updated:${NC}"
echo "  ✓ sig-01/koneksi.php"
echo "  ✓ sig-02/koneksi.php"
echo "  ✓ sig-03/koneksi.php"
echo "  ✓ .env.example"
echo "  ✓ Dockerfile"
echo "  ✓ railway.json"
echo "  ✓ .gitignore"
echo ""

echo -e "${GREEN}Git status:${NC}"
git log --oneline -1

echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Push to GitHub: ${BLUE}git push origin main${NC}"
echo "2. Go to ${BLUE}https://railway.app${NC}"
echo "3. Create new project → Deploy from GitHub"
echo "4. Select this repo → Click Deploy"
echo "5. Add MySQL service from Railway dashboard"
echo "6. Set environment variables (DB_HOST, DB_USER, DB_PASS, etc.)"
echo "7. Create databases and import SQL files"
echo "8. Test endpoints!"
echo ""
echo -e "${GREEN}For detailed guide, see: railway-deployment-guide.md${NC}"
