#!/bin/bash
set -e

# Fix MPM conflict
/bin/rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf || true
/usr/sbin/a2enmod mpm_prefork

# Railway injects $PORT — make Apache listen on it (default 80)
APACHE_PORT=${PORT:-80}
sed -i "s/^Listen 80$/Listen ${APACHE_PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${APACHE_PORT}>/" /etc/apache2/sites-enabled/000-default.conf

echo "Apache listening on port ${APACHE_PORT}"
exec /usr/local/bin/apache2-foreground
