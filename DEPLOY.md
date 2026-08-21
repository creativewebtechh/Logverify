# Deploying LogVerify to cPanel

This guide covers deploying the LogVerify platform (Laravel 12 + MySQL) to a typical
shared cPanel host (cPanel + Apache + PHP 8.2). It also applies to any hosting where
the web document root points at a subdirectory of the project.

> **Stack summary:** Laravel 12 · Livewire 3 · Alpine.js · Tailwind (Vite) · MySQL ·
> queue: database · scheduler: cron. No Redis or external queue server required.

---

## 1. Prerequisites

| Requirement | Notes |
|---|---|
| PHP **8.2+** | extension `openssl`, `pdo_mysql`, `mbstring`, `tokenizer`, `xml`, `ctype`, `curl`, `fileinfo`, `bcmath`, `zip` |
| Composer 2.x | available in cPanel Terminal or via SSH |
| Node.js 18+ (build machine) | only needed to compile assets |
| MySQL 5.7+ / MariaDB 10.3+ | a database + user created from cPanel |
| Terminal/SSH access | to run `composer`, `php artisan`, cron |

If cPanel's default PHP is older than 8.2, change it under **Select PHP Version**.

---

## 2. Upload the code

Upload the project to `~/logverify` (not into `public_html`):

- Use **File Manager → Upload**, or
- `git clone` from your repository into `~/logverify`.

Do **not** upload `vendor/`, `node_modules/`, `.env`, or the local `public/build`
folder — they are regenerated on the server.

```
~/logverify
├── app/
├── public/          ← document root will point here
├── resources/
├── ... (Laravel structure)
```

---

## 3. Create the database

1. cPanel → **MySQL® Databases** → create a database, e.g. `user_logverify`.
2. Create a database user and add it to the database with **ALL PRIVILEGES**.
3. Note the hostname — it is usually `localhost` on shared hosting.

---

## 4. Configure the environment

SSH/Terminal into the server and:

```bash
cd ~/logverify

# Fresh install
cp .env.example .env

# Or, for an existing install, keep your current .env
php artisan key:generate
```

Edit `.env`:

```ini
APP_NAME="LogVerify"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=user_logverify
DB_USERNAME=user_logverify
DB_PASSWORD=your-strong-db-password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# Gateway keys (Paystack and/or Monnify). Leave both in TEST_MODE until go-live.
PAYSTACK_PUBLIC_KEY=
PAYSTACK_SECRET_KEY=
PAYSTACK_WEBHOOK_SECRET=
PAYSTACK_TEST_MODE=true

MONNIFY_CLIENT_KEY=
MONNIFY_CLIENT_SECRET=
MONNIFY_CONTRACT_CODE=
MONNIFY_TEST_MODE=true
```

> **Important:** `APP_DEBUG` must be `false` in production. The naira symbol is
> hardcoded in `config/app.php` and is independent of the `.env`.

---

## 5. Install dependencies

```bash
cd ~/logverify

composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Build front-end assets

Assets must be built with Node then committed/synced, **or** built on the server if
Node is available:

```bash
npm install
npm run build        # outputs to public/build
```

The built `public/build` folder and its manifest must be present on the server, or
the UI will render unstyled.

---

## 6. Point the document root at `public/`

### Option A — Main domain (recommended)

Use cPanel → **Setup Node.js App** or **MultiPHP Manager**, or via **Terminal**:

```bash
# Only if the app owns the whole domain / document root
rm -f ~/public_html
ln -s ~/logverify/public ~/public_html
```

Afterwards `public_html` is a symlink to the Laravel `public` folder.

### Option B — Addon domain / subdomain (keeps the app private)

1. cPanel → **Addon Domains** → create `admin.yourdomain.com` with **Document Root**
   set to: `~/logverify/public` (subdomain `sub.yourdomain.com` → `public/logverify` if you prefer).
2. The rest of the code stays outside the document root, so `storage/` and `.env`
   are never web-accessible.

### `.htaccess` (Apache)

`public/.htaccess` ships with Laravel and is already correct:

```apache
RewriteEngine On
# ... standard Laravel redirect to public/index.php
```

Verify the host rewrites `index.php` correctly by loading your domain. If you see a
directory listing or a 500, check `storage/logs/laravel.log`.

---

## 7. Storage & cache permissions

```bash
cd ~/logverify

chmod -R 775 storage bootstrap/cache
chown -R $(whoami):$(whoami) storage bootstrap/cache
```

If that fails on shared hosting, set **File Manager → Permissions** to `755`/`775`
for `storage` and `bootstrap/cache` and try again. The Laravel scheduler and queue
worker both write into `storage/`.

---

## 8. Background jobs — scheduler + queue (critical)

### 8.1 One-time table creation

Run migrations (step 5) — this creates the `jobs` table needed by the database
queue driver.

### 8.2 Scheduler cron

Add a single cron entry from cPanel → **Cron Jobs** (or crontab):

```
* * * * * cd ~/logverify && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler drives renewals, provider balance syncs, and any other recurring
tasks. If cPanel blocks `cd`, use the full PHP path from **MultiPHP Manager**
(e.g. `/usr/local/bin/php`).

### 8.3 Queue worker

The app processes orders asynchronously with the database queue. Add a long-running
worker via cron (runs every minute; only processes if work is queued):

```
* * * * * cd ~/logverify && php artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

Or, if you prefer a persistent worker, use **Setup Python App / Setup Node.js App**
(keep-alive) running:

```
php artisan queue:work
```

> Orders are still placed synchronously by the `ProviderRouter`; the queue is used
> for long-running provider callbacks. Without a worker, background jobs accumulate
> in the `jobs` table, so make sure at least the cron worker above runs.

---

## 9. Create the first admin

```bash
cd ~/logverify

# From the seeded command (runs with migrations)
php artisan db:seed --class=AdminSeeder --force   # or whatever your seeder is called
```

Or register a normal account at `/register` and then promote it:

```bash
php artisan tinker --execute="App\Models\User::where('email','you@domain.com')->update(['role'=>'admin']);"
```

Roles: `admin`, `agent`, `customer`. See `User::ROLE_*` constants.

---

## 10. Configure providers (admin panel)

Providers are **stored in the database and configured from the admin panel** — no
`.env` keys needed. Log in as admin → **Admin → Integrations**:

1. Add a provider per channel:
   - **Virtual Numbers**: driver `generic` (JSON API) or `grizzly` (sms-activate style).
   - **Social Boost**: driver `generic` or `smmpanel` (SMM panel v2).
2. Set `base_url`, the API endpoints, and paste the API key (it is **encrypted at
   rest** and only ever shown masked as `••••••••…`).
3. Set `priority` (lower = tried first) and mark it active.
4. Use **Test** (health-check) and **Sync** (balance/services) to verify.

When a provider errors, `ProviderRouter` automatically fails over to the next
active provider for the channel; every attempt is logged in **Admin → Dashboard →
Provider usage** (`provider_logs`). Orders for a channel with no configured provider
are simulated locally so the store keeps working during setup.

If you upgraded from an install that used the legacy settings panel, run:

```bash
php artisan migrate --force
```

The `create_providers_table` migration imports the legacy
`provider.{channel}.*` settings automatically.

---

## 11. Payment webhooks

Set up webhooks in your gateway dashboard, pointed at:

```
https://yourdomain.com/paystack/webhook
https://yourdomain.com/monnify/webhook
```

(The legacy `/webhook/*` paths still work for backwards compatibility but the
canonical `/paystack/webhook` and `/monnify/webhook` routes above are preferred.)

Store the webhook **secret** in `.env` (`PAYSTACK_WEBHOOK_SECRET`,
`MONNIFY_CLIENT_SECRET`). Webhooks verify signatures and are rate-limited. Keep
`*_TEST_MODE=true` while testing against sandbox keys.

---

## 12. Go-live checklist

- [ ] `APP_ENV=production`, `APP_DEBUG=false`
- [ ] `APP_URL` uses `https://`
- [ ] `composer install --no-dev --optimize-autoloader` done
- [ ] `npm run build` assets present in `public/build`
- [ ] Config/route/view caches built (`php artisan config:cache route:cache view:cache`)
- [ ] `storage` + `bootstrap/cache` writable
- [ ] Scheduler cron + queue worker running
- [ ] Live gateway keys + webhook secrets in `.env`, webhooks registered
- [ ] Provider credentials added in Admin → Integrations and tested
- [ ] First admin account created
- [ ] Force HTTPS + set `SESSION_SECURE_COOKIE=true` in `.env`
- [ ] Force HTTPS in `.htaccess` (standard rule) if the host does not terminate TLS
- [ ] `php artisan migrate:status` shows all migrations run

---

## 13. Updating the app

```bash
cd ~/logverify
git pull                                # or re-upload changed files
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
# rebuild assets if the UI changed
npm run build
```

Back up the database before each release:

```bash
mysqldump -u user -p user_logverify > backup-$(date +%F).sql
```

---

## Troubleshooting

| Symptom | Fix |
|---|---|
| Blank page / 500 after deploy | `APP_DEBUG=false` hides errors — check `storage/logs/laravel.log` |
| Styled UI missing | `public/build` manifest missing — run `npm run build` and upload |
| Provider calls fail | check `provider_logs` rows in Admin → Dashboard; verify `base_url`/endpoints in Integrations |
| Background jobs never run | confirm cron for `schedule:run` + `queue:work`; check `jobs` table |
| Login/security errors | ensure `APP_KEY` set (`php artisan key:generate`), sessions cleared after domain change |
