# 🚀 HemoTracka Deployment Guide

This document provides a step-by-step guide to hosting the HemoTracka API on local servers (XAMPP/WAMP) and production environments (Linux/VPS/Plesk).

---

## 💻 1. Local Hosting (XAMPP/WAMP)

1.  **Extract/Clone** the project into your `htdocs` or `www` directory.
2.  **Database**: Open phpMyAdmin and create a database named `hemotracka`.
3.  **Environment**: 
    - Copy `.env.example` to `.env`.
    - Set `DB_DATABASE=hemotracka`.
    - Set `DB_USERNAME=root` and `DB_PASSWORD=` (blank by default in XAMPP).
4.  **Dependencies**:
    - Open terminal in project folder.
    - Run `composer install`.
5.  **Initialization**:
    - Run `php artisan key:generate`.
    - Run `php artisan migrate --seed` (This creates tables and initial data).
6.  **Serve**:
    - Run `php artisan serve`.
    - Access via `http://localhost:8000/api`.

---

## 🌐 2. Production Hosting (Linux/VPS)

### Prerequisites
- PHP 8.1 or higher.
- MySQL 5.7+ or MariaDB.
- Nginx or Apache.

### Setup Steps
1.  **Upload Files**: Upload the project to your server (e.g., `/var/www/hemotracka`).
2.  **Permissions**:
    - Ensure `storage` and `bootstrap/cache` are writable by the web server:
      ```bash
      chmod -R 775 storage bootstrap/cache
      chown -R www-data:www-data storage bootstrap/cache
      ```
3.  **Environment**:
    - Rename `.env.example` to `.env`.
    - Update `APP_ENV=production` and `APP_DEBUG=false`.
    - Set `APP_URL` to your domain (e.g., `https://api.hemotracka.com`).
    - Input production `DB_*` credentials.
4.  **Key & Config**:
    ```bash
    php artisan key:generate --force
    php artisan config:cache
    php artisan route:cache
    ```
5.  **Migrations**:
    ```bash
    php artisan migrate --force
    ```

---

## 🛠️ Common Troubleshooting

### ❌ "The only supported ciphers are AES-128-CBC and AES-256-CBC..."
**Cause**: Missing or empty `APP_KEY` in `.env`.
**Fix**: Run `php artisan key:generate`.

### ❌ "Access denied for user 'root'@'localhost'..."
**Cause**: Incorrect database credentials in `.env`.
**Fix**: double-check `DB_USERNAME` and `DB_PASSWORD`.

### ❌ "500 Internal Server Error"
**Cause**: Often folder permissions or a syntax error in `.env` (like missing quotes around a string with spaces).
**Fix**: Check `storage/logs/laravel.log` for the specific error.

### ❌ "CORS error"
**Cause**: The frontend domain is not allowed.
**Fix**: Update `config/cors.php` or ensure `SANCTUM_STATEFUL_DOMAINS` in `.env` matches your frontend URL.
