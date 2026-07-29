# 04 — Deployment on Hostinger

## 1. Which plan you need

Laravel on Hostinger works well **provided** the plan gives you SSH, Composer, and cron.

| Plan | SSH | Composer | Cron | Verdict |
|---|---|---|---|---|
| Single / Premium Shared | Limited | Sometimes | Yes | Workable but painful. Not recommended. |
| **Business Shared** | Yes | Yes | Yes | **Minimum viable.** Fine up to moderate traffic. |
| Cloud Startup | Yes | Yes | Yes | Comfortable. More resources, better isolation. |
| **VPS** | Full root | Yes | Yes | **Recommended once you have real donation traffic.** Supervisor, Redis, browsershot all become available. |

**Practical recommendation:** launch on Business or Cloud Startup. Plan to migrate to a VPS when
either (a) queue latency becomes visible to donors, or (b) monthly donation volume passes roughly
1,000 transactions. Write the deploy script so the migration is a weekend, not a project.

Requirements to verify before you commit: **PHP 8.3+**, MySQL 8, `pdo_mysql`, `mbstring`, `openssl`,
`gd`, `zip`, `bcmath`, `fileinfo`, `intl`, `exif`.

## 2. Directory layout

Hostinger serves from `public_html`. Laravel's public directory must be what's exposed, and the
application code must sit **outside** the web root.

```
/home/uXXXXXXX/
├── domains/visiongoodworkglobalfoundation.org/
│   └── public_html/          ← web root, contains ONLY Laravel's public/ contents
│       ├── index.php         (modified — see below)
│       ├── .htaccess
│       ├── build/
│       ├── storage/          (symlink → ../../../app/storage/app/public)
│       └── favicon.ico
└── app/                      ← application code, NOT web-accessible
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── resources/
    ├── routes/
    ├── storage/
    ├── vendor/
    ├── .env
    └── artisan
```

Edit `public_html/index.php` to point at the relocated app:

```php
require __DIR__.'/../../../app/vendor/autoload.php';
$app = require_once __DIR__.'/../../../app/bootstrap/app.php';
```

> Adjust the number of `../` segments to match your actual paths. Get this wrong and you'll either
> get a 500 or — much worse — expose `.env` over HTTP. **After deploying, visit
> `https://visiongoodworkglobalfoundation.org/.env` and confirm you get a 404.** Do this every single time.

## 3. First deployment

```bash
ssh -p 65002 uXXXXXXX@your-server-ip

cd ~/
git clone https://github.com/you/vgwgf-platform.git app
cd app

composer install --no-dev --optimize-autoloader

cp .env.example .env
nano .env                      # fill in everything — see §6
php artisan key:generate

php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=SettingsSeeder --force
php artisan db:seed --class=CampaignCategorySeeder --force
php artisan db:seed --class=EmailTemplateSeeder --force
php artisan db:seed --class=DocumentTemplateSeeder --force
php artisan db:seed --class=AdminUserSeeder --force

# assets: build LOCALLY and commit, or build here if node is available
npm ci && npm run build

# expose public/
rm -rf ~/domains/visiongoodworkglobalfoundation.org/public_html
ln -s ~/app/public ~/domains/visiongoodworkglobalfoundation.org/public_html
# if symlinking the web root is blocked by your plan, copy public/ contents
# instead and re-copy on each deploy

php artisan storage:link

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

chmod -R 775 storage bootstrap/cache
```

**Change the seeded admin password immediately after first login.**

## 4. Cron: scheduler and queue

Hostinger shared plans have no Supervisor, so the queue worker runs from cron. Two entries:

```cron
# Laravel scheduler — every minute
* * * * * cd /home/uXXXXXXX/app && /usr/bin/php8.3 artisan schedule:run >> /dev/null 2>&1

# Queue worker — drains the queue then exits, so cron can restart it cleanly
* * * * * cd /home/uXXXXXXX/app && /usr/bin/php8.3 artisan queue:work --stop-when-empty --tries=3 --timeout=90 --max-time=55 >> storage/logs/queue.log 2>&1
```

**Why `--stop-when-empty` and `--max-time=55`:** a long-running `queue:work` on shared hosting gets
killed by the process manager at unpredictable times, sometimes mid-job. Draining and exiting within
the minute means cron restarts it deterministically, and `--max-time=55` guarantees it exits before
the next cron tick so you don't stack overlapping workers.

**The cost:** up to 60 seconds of latency before a PDF job starts. Acceptable for receipts and
notices — a donor waiting 40 seconds for a receipt email is fine. Not acceptable if you later add
anything interactive that depends on the queue. That's the moment to move to a VPS with Supervisor.

On a **VPS**, use Supervisor instead:

```ini
[program:ngo-worker]
command=php /var/www/ngo/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=2
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/ngo/storage/logs/worker.log
stopwaitsecs=3600
```

### Scheduled tasks (`routes/console.php`)

```php
Schedule::job(new SyncSubscriptionStatus)->dailyAt('02:00');
Schedule::job(new RecalculateAllCampaignTotals)->dailyAt('03:00');
Schedule::job(new RetryFailedSubscriptionCharges)->dailyAt('10:00');
Schedule::command('backup:database')->dailyAt('01:00');
Schedule::command('activitylog:clean')->weekly();
Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::job(new SendMonthlyAdminSummary)->monthlyOn(1, '09:00');
Schedule::command('sitemap:generate')->daily();
```

## 5. Deploy script

`deploy.sh` in the project root:

```bash
#!/usr/bin/env bash
set -e

APP_DIR=/home/uXXXXXXX/app
PHP=/usr/bin/php8.3

cd $APP_DIR

$PHP artisan down --render="errors::503" --retry=60

git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction

$PHP artisan migrate --force

$PHP artisan config:clear && $PHP artisan config:cache
$PHP artisan route:clear  && $PHP artisan route:cache
$PHP artisan view:clear   && $PHP artisan view:cache
$PHP artisan event:clear  && $PHP artisan event:cache

$PHP artisan queue:restart
$PHP artisan up

echo "Deployed: $(git rev-parse --short HEAD)"
```

`chmod +x deploy.sh`, then `./deploy.sh` on each release.

Build frontend assets locally and commit `public/build/` — Node is unreliable on shared hosting and
you don't want a failed `npm ci` to be what takes the site down.

## 6. `.env` reference

```dotenv
APP_NAME="Vision Good Work Global Foundation"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false                 # NEVER true in production
APP_URL=https://visiongoodworkglobalfoundation.org
APP_TIMEZONE=Asia/Kolkata
APP_LOCALE=en

LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=uXXXXXXX_ngo
DB_USERNAME=uXXXXXXX_ngo
DB_PASSWORD=

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

CACHE_STORE=database           # file also fine; no Redis on shared plans
QUEUE_CONNECTION=database

FILESYSTEM_DISK=public

# Mail — use a real transactional provider, not shared-hosting sendmail.
# Shared-host mail lands in spam and 80G receipts must arrive reliably.
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com   # or Brevo / Mailgun / Postmark
MAIL_PORT=465
MAIL_USERNAME=noreply@visiongoodworkglobalfoundation.org
MAIL_PASSWORD=
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=noreply@visiongoodworkglobalfoundation.org
MAIL_FROM_NAME="${APP_NAME}"
MAIL_ADMIN_ADDRESS=admin@visiongoodworkglobalfoundation.org

RAZORPAY_KEY_ID=rzp_live_xxxxx
RAZORPAY_KEY_SECRET=
RAZORPAY_WEBHOOK_SECRET=
RAZORPAY_CURRENCY=INR

DOCUMENT_QR_BASE_URL="${APP_URL}/verify"
PDF_FONT_DIR=/home/uXXXXXXX/app/storage/fonts

SENTRY_LARAVEL_DSN=

ADMIN_SEED_EMAIL=admin@visiongoodworkglobalfoundation.org
ADMIN_SEED_PASSWORD=            # change immediately after first login
```

## 7. Razorpay webhook

In the Razorpay dashboard → Settings → Webhooks:

- **URL:** `https://visiongoodworkglobalfoundation.org/webhooks/razorpay`
- **Secret:** generate one, put it in `RAZORPAY_WEBHOOK_SECRET`
- **Events:** `payment.captured`, `payment.failed`, `refund.created`, `subscription.activated`,
  `subscription.charged`, `subscription.pending`, `subscription.halted`, `subscription.cancelled`,
  `subscription.completed`

Exclude the webhook route from CSRF (`bootstrap/app.php` → `validateCsrfTokens(except: [...])`).

**Test in Razorpay test mode first.** Complete a full donation, confirm the webhook arrives, confirm
the receipt generates. Only then switch to live keys.

## 8. SSL & security headers

Enable Hostinger's free Let's Encrypt SSL, then force HTTPS in `AppServiceProvider`:

```php
if ($this->app->environment('production')) {
    URL::forceScheme('https');
}
```

`.htaccess` additions in the web root:

```apache
# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security headers
Header always set X-Content-Type-Options "nosniff"
Header always set X-Frame-Options "SAMEORIGIN"
Header always set Referrer-Policy "strict-origin-when-cross-origin"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"

# Block dotfiles
<FilesMatch "^\.">
    Require all denied
</FilesMatch>
```

## 9. PDF fonts (Hindi support)

dompdf will render Devanagari as empty boxes unless you embed a font. Do this once, in Phase 2:

```bash
mkdir -p storage/fonts
# download Noto Sans Devanagari .ttf into storage/fonts/
php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

`config/dompdf.php`:

```php
'font_dir'   => storage_path('fonts/'),
'font_cache' => storage_path('fonts/'),
'defines' => [
    'DOMPDF_ENABLE_REMOTE' => true,
    'DOMPDF_UNICODE_ENABLED' => true,
],
```

In the PDF Blade templates:

```css
@font-face {
    font-family: 'NotoDevanagari';
    src: url('{{ storage_path("fonts/NotoSansDevanagari-Regular.ttf") }}') format('truetype');
    font-weight: normal;
}
body { font-family: 'NotoDevanagari', DejaVu Sans, sans-serif; }
```

`storage/fonts/` must be writable — dompdf caches compiled font metrics there.

## 10. Backups

Hostinger's own backups are a safety net, not a strategy. Add your own:

```bash
composer require spatie/laravel-backup
```

`config/backup.php`: include the DB and `storage/app/public`, exclude `vendor` and `node_modules`.
Schedule nightly, keep 7 daily + 4 weekly + 3 monthly, and ship copies off-site (Google Drive, S3,
Backblaze — anywhere that isn't the same server).

**Restore-test quarterly.** An untested backup is a rumour. Restore into a scratch database and
confirm donation counts and receipt numbers match production.

## 11. Post-deploy checklist

- [ ] `https://visiongoodworkglobalfoundation.org/.env` returns 404
- [ ] `https://visiongoodworkglobalfoundation.org/storage/` does not list files
- [ ] `APP_DEBUG=false` — trigger a deliberate error and confirm no stack trace is shown
- [ ] SSL Labs grade A
- [ ] Cron entries present and firing (check `storage/logs/queue.log`)
- [ ] `php artisan queue:failed` is empty after a smoke test
- [ ] A test donation completes and the receipt email arrives
- [ ] Razorpay webhook shows successful deliveries in their dashboard
- [ ] QR verification page loads over HTTPS on a phone
- [ ] Hindi renders correctly in a generated PDF
- [ ] Backup ran and the file exists off-site
- [ ] Sentry received a deliberately triggered test error
- [ ] Google Search Console verified, sitemap submitted
- [ ] Admin seed password changed

## 12. When to leave shared hosting

Move to a VPS when any of these becomes true:

- Queue latency is visible to users (receipts taking minutes)
- More than ~1,000 donations/month
- You need `spatie/browsershot` for better-looking PDFs (requires headless Chrome)
- You want Redis for cache, sessions, and queues
- You need Supervisor for reliable, always-on workers
- Campaign traffic spikes are causing 503s

The migration is mostly: provision, install PHP/MySQL/Nginx/Supervisor/Redis, `git clone`, restore
DB, repoint DNS. A day's work if the deploy script already exists — which is exactly why it should.

---

Next: [`05-CONVENTIONS.md`](05-CONVENTIONS.md)
