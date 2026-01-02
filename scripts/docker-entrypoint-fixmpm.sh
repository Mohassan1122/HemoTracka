#!/bin/bash
set -e
# Remove all MPM modules except prefork from mods-enabled and mods-available
find /etc/apache2/mods-enabled/ -type l -name 'mpm_*.load' ! -name 'mpm_prefork.load' -delete
find /etc/apache2/mods-enabled/ -type l -name 'mpm_*.conf' ! -name 'mpm_prefork.conf' -delete
find /etc/apache2/mods-available/ -type f -name 'mpm_*.load' ! -name 'mpm_prefork.load' -delete
find /etc/apache2/mods-available/ -type f -name 'mpm_*.conf' ! -name 'mpm_prefork.conf' -delete
# Ensure prefork is enabled
a2enmod mpm_prefork || true
# Start Apache in foreground
exec apache2-foreground
