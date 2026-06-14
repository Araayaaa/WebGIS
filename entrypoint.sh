#!/bin/bash

APACHE_PORT=${PORT:-80}
echo "Configuring Apache on port $APACHE_PORT"

# Fix MPM conflict
rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf 2>/dev/null || true
a2enmod mpm_prefork || true

# Overwrite ports.conf with the correct port
echo "Listen $APACHE_PORT" > /etc/apache2/ports.conf

# Update VirtualHost to match the port (match any port number, not just 80)
sed -i "s/VirtualHost \*:[0-9]*/VirtualHost *:$APACHE_PORT/g" /etc/apache2/sites-available/000-default.conf 2>/dev/null || true

echo "Apache port config done."

# Initialize RBAC system (safe to run multiple times)
echo "Initializing RBAC system..."
php /var/www/html/sig-03/init_rbac.php || true

echo "Starting Apache on port $APACHE_PORT..."
exec apache2-foreground
