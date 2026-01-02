#!/bin/bash
set -e
# Force-disable all MPMs except prefork
if command -v a2dismod >/dev/null 2>&1; then
	a2dismod mpm_event mpm_worker mpm_itk mpm_threadpool mpm_prefork || true
	a2enmod mpm_prefork || true
fi
# Restart Apache to apply changes (if running)
if pidof apache2 > /dev/null; then
	apachectl -k restart || true
fi
# Remove all MPM modules except prefork from mods-enabled and mods-available
find /etc/apache2/mods-enabled/ -type l -name 'mpm_*.load' ! -name 'mpm_prefork.load' -delete
find /etc/apache2/mods-enabled/ -type l -name 'mpm_*.conf' ! -name 'mpm_prefork.conf' -delete
find /etc/apache2/mods-available/ -type f -name 'mpm_*.load' ! -name 'mpm_prefork.load' -delete
find /etc/apache2/mods-available/ -type f -name 'mpm_*.conf' ! -name 'mpm_prefork.conf' -delete
# Ensure prefork is enabled
a2enmod mpm_prefork || true
# Start Apache in foreground
exec apache2-foreground
