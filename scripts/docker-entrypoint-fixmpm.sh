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
# Start Apache in foreground
exec apache2-foreground
