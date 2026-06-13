#!/bin/bash
set -ex

echo "=== entrypoint: listing MPM files ==="
ls /etc/apache2/mods-enabled/mpm_* 2>/dev/null || echo "no MPM files found"

echo "=== entrypoint: removing MPM files ==="
/bin/rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf || true

echo "=== entrypoint: enabling mpm_prefork ==="
/usr/sbin/a2enmod mpm_prefork

echo "=== entrypoint: starting apache ==="
exec /usr/local/bin/apache2-foreground
