#!/bin/bash
set -e
# Always ensure only mpm_prefork is enabled before starting Apache
if command -v a2dismod >/dev/null 2>&1; then
  a2dismod mpm_event mpm_worker || true
  a2enmod mpm_prefork || true
fi
# Start Apache in foreground
exec apache2-foreground
