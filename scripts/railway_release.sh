#!/usr/bin/env sh
set -e

echo "Running release tasks..."
php artisan migrate --force
php artisan config:cache
php artisan route:cache
echo "Release tasks completed."
