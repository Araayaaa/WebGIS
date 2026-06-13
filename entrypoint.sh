#!/bin/bash

# Fix MPM conflict
rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf 2>/dev/null || true
a2enmod mpm_prefork || true

# Railway injects $PORT — configure Apache to listen on it
APACHE_PORT=${PORT:-80}
echo "Starting Apache on port $APACHE_PORT"

sed -i "s/^Listen 80$/Listen $APACHE_PORT/" /etc/apache2/ports.conf || true
sed -i "s/:80>/:$APACHE_PORT>/" /etc/apache2/sites-enabled/000-default.conf || true

exec apache2-foreground
