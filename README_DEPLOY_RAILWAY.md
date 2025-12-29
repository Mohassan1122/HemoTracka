# Deploying HemoTracka to Railway (quick guide)

This guide covers a minimal, fast path to get HemoTracka running on Railway with a MySQL database so you can hand the API URL to your frontend.

Prerequisites
- A Railway account (https://railway.app/) and GitHub access to connect the repo.
- The repository contains a working `Dockerfile` (the project already has one).

Steps

1. Create a Railway project
   - Log in to Railway and create a new project.
   - Choose "Deploy from GitHub" and connect this repository.

2. Add a MySQL plugin
   - In the Railway project, click "Add Plugin" → "MySQL".
   - Railway will provision a managed MySQL instance and expose environment variables.

3. Environment variables
   - Open the Railway Variables panel and set environment variables (or let Railway inject plugin vars then map them):
     - `APP_ENV=production`
     - `APP_DEBUG=false`
     - `APP_KEY` — run locally: `php artisan key:generate --show` and paste the output.
     - `APP_URL` — set to the Railway assigned URL (e.g., `https://<project>.up.railway.app`).
     - `DB_CONNECTION=mysql`
     - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — from the MySQL plugin.

   - You can use the included `.env.example` as a reference when setting variables.

4. Build settings
   - The repository contains a `Dockerfile`. Railway will build using that by default.
   - If Railway does not auto-detect, set the build to use Docker.

5. Run migrations and cache config
   - Once the service is deployed, run migrations in the Railway console or as a release command:

```bash
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

6. Confirm the API
   - Railway provides a public URL for the web service. Confirm endpoints like `/api` or `/health` and give that base URL to your frontend.

Notes & gotchas
- If you run into permission issues, ensure `storage` and `bootstrap/cache` are writable by the web user (Dockerfile handles typical permissions).
- Railway free tier may sleep or have connection limits — fine for integration tests but not production.
- If you prefer Postgres, Railway offers it — update `DB_CONNECTION` and providers accordingly.

Optional: Automated release command
- In Railway you can add a "Release Command" to run migrations automatically after each deploy. Use:

```bash
php artisan migrate --force && php artisan config:cache
```

If you want, I can:
- Prepare a small GitHub Action to run `php artisan key:generate` and produce a secret for Railway,
- Or walk you through connecting the repo and setting the Railway environment variables interactively.

Automated GitHub Action (what I added)
- There is a GitHub Actions workflow at `.github/workflows/railway_deploy.yml` that runs on pushes to `main`.
- The workflow uses the Railway CLI to deploy the repository and then runs a release script to migrate and cache config.

Required GitHub repository secrets (you must add these in Settings → Secrets):
- `RAILWAY_API_KEY` — create in Railway (Personal Access Token).
- `RAILWAY_PROJECT_ID` — the Railway project ID to `link` before deploy.

After adding those secrets, push to `main` and the Action will deploy automatically. If you prefer, I can help generate the `APP_KEY` and add any extra deploy automation.

