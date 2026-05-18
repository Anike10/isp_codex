# Deployment Runbook

This file documents how to update the live Laravel app on **finalaccess.com**.

Keep passwords, API keys, database credentials, and SSH private keys out of this
repository. Use the server owner, password manager, or approved secure note when
credentials are needed.

## Production Server

```text
Domain: finalaccess.com
SSH host: 162.4.6.7
SSH user: anike
Laravel root: /home/finalaccess.com/public_html
Runtime user: final4810
Panel/server: CyberPanel / OpenLiteSpeed
Environment: production
APP_URL: finalaccess.com
```

Known SSH host key fingerprint:

```text
SHA256:CL6hp3uAz5yEuUYaRO3G2j5K4sW1UPyLCXmbcovDETQ
```

## What To Deploy

The production app is a Git checkout of:

```text
https://github.com/Anike10/isp_codex.git
branch: main
```

Normal deployment flow:

1. Finish and test locally.
2. Commit the local changes.
3. Push `main` to GitHub.
4. SSH to the production server.
5. Pull the latest `main` as the site user.
6. Clear Laravel caches.
7. Verify the site.

## Local Pre-Deploy Checklist

Run from the local project root:

```bash
git status --short
php artisan test
git log --oneline -3
```

If the change touches routes, controllers, services, or migrations, also run:

```bash
php artisan route:list --except-vendor
```

Commit and push:

```bash
git add .
git commit -m "Short useful message"
git push origin main
```

If `git push` hangs or fails, confirm the local machine has GitHub network/auth
access. Do not deploy unpushed local-only changes unless there is an emergency.

## Connect To Production

Windows PowerShell with PuTTY:

```powershell
& 'C:\Program Files\PuTTY\plink.exe' -ssh anike@162.4.6.7 -hostkey 'SHA256:CL6hp3uAz5yEuUYaRO3G2j5K4sW1UPyLCXmbcovDETQ'
```

OpenSSH:

```bash
ssh anike@162.4.6.7
```

After login, most app commands should be run as the site runtime user:

```bash
sudo -u final4810 bash
cd /home/finalaccess.com/public_html
```

## Check Server State Before Pulling

Always check for local server edits before updating:

```bash
cd /home/finalaccess.com/public_html
git status -sb
git log --oneline -3
git remote -v
```

If `git status` shows modified files, do not discard them blindly. Decide whether
they are expected server-only files, old hotfixes, or changes that must be
committed first.

Known server-local files/changes seen on 2026-05-18:

```text
M app/Models/InvoiceItem.php
M database/migrations/2026_05_04_000001_update_invoices_allow_multiple_per_month.php
M database/migrations/2026_05_04_000002_create_invoice_items_table.php
?? .htaccess
```

These were not part of the zebra print/list update and should not be reverted
without review.

## Recommended Deploy Command

Run on the server as `final4810`:

```bash
cd /home/finalaccess.com/public_html
git pull --ff-only origin main
php artisan optimize:clear
```

Use `--ff-only` so the deployment stops instead of creating a merge commit on
the server.

If migrations are included in the new commit, run:

```bash
php artisan migrate --force
php artisan optimize:clear
```

If Composer dependencies changed:

```bash
composer install --no-dev --optimize-autoloader
php artisan optimize:clear
```

Current OLT live polling requires:

```text
phpseclib/phpseclib 3.0.52
OLT SSH: legacy host keys ssh-rsa/ssh-dss are supported by OltSshClient
Default HSGQ context: enable, config
Default HSGQ PON ports: 1,2,3,4,5,6,7,8
Default HSGQ show commands: show onu-info all, show optical-info
```

Run `composer install --no-dev --optimize-autoloader` on production after
deploying changes that include `composer.json` or `composer.lock`.

If frontend build files are introduced later and `public/build` is used:

```bash
npm ci
npm run build
```

Current UI is Blade/CSS only, so no frontend build is normally required.

## One-Line Deploy From Local PowerShell

Use this only after the code is committed and pushed to GitHub.

```powershell
& 'C:\Program Files\PuTTY\plink.exe' -ssh anike@162.4.6.7 -hostkey 'SHA256:CL6hp3uAz5yEuUYaRO3G2j5K4sW1UPyLCXmbcovDETQ' "sudo -u final4810 bash -lc 'cd /home/finalaccess.com/public_html && git pull --ff-only origin main && php artisan optimize:clear'"
```

If the server asks for a sudo password, enter it interactively. Do not place the
password in committed scripts or documentation.

## Backups Before Risky Changes

For simple CSS/view changes, a Git commit is usually enough rollback protection.
Before database migrations, large refactors, or payment/billing changes, create
a server backup.

Code backup:

```bash
cd /home/finalaccess.com
sudo tar -czf deploy_backups/public_html_$(date +%Y%m%d_%H%M%S).tgz public_html
```

Database backup from the app:

```bash
cd /home/finalaccess.com/public_html
php artisan db:show
```

Use the app's `/backup/database` route when a logged-in admin backup is enough.
For large databases, prefer `mysqldump` with credentials from `.env`.

## Verify After Deploy

Server checks:

```bash
cd /home/finalaccess.com/public_html
git status -sb
git log --oneline -1
php artisan about --only=environment
php artisan route:list --except-vendor
```

HTTP check:

```bash
curl -I -L https://finalaccess.com
```

If the local machine does not trust the certificate chain, this command may fail
with a certificate warning. For a quick status-only check:

```bash
curl -k -I -L https://finalaccess.com
```

Expected behavior for a guest request:

```text
https://finalaccess.com -> 302 redirect to /login -> 200 OK
```

App log check:

```bash
tail -n 100 storage/logs/laravel.log
```

## Rollback

If the deployment was a normal fast-forward pull and no migration/data change is
involved, rollback to the previous commit:

```bash
cd /home/finalaccess.com/public_html
git log --oneline -5
git reset --hard <previous_commit>
php artisan optimize:clear
```

Only use `git reset --hard` when you are certain server-local modified files do
not need to be preserved. If there are local edits, copy or commit them first.

Safer view-only rollback:

```bash
git checkout <previous_commit> -- resources/views/path/to/file.blade.php
php artisan optimize:clear
```

## Permissions And Ownership

Production files are owned by:

```text
final4810:final4810
```

If files are uploaded manually, fix ownership:

```bash
sudo chown -R final4810:final4810 /home/finalaccess.com/public_html
```

Writable Laravel directories:

```bash
storage
bootstrap/cache
```

If cache/log writes fail:

```bash
sudo chown -R final4810:final4810 storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

## Cron / Scheduled Commands

Production Laravel root:

```text
/home/finalaccess.com/public_html
```

Recommended cron entries use this path:

```cron
* * * * * cd /home/finalaccess.com/public_html && php artisan mikrotik:sync-router-users >> /dev/null 2>&1
5 0 * * * cd /home/finalaccess.com/public_html && php artisan billing:disable-overdue-customers >> /dev/null 2>&1
* * * * * cd /home/finalaccess.com/public_html && php artisan schedule:run >> /dev/null 2>&1
```

Confirm cron is installed for the correct user before changing it:

```bash
crontab -l
sudo -u final4810 crontab -l
```

## Production SMS Webhook

SmsForwarder production URL:

```text
https://finalaccess.com/api/bkash/sms
```

Method:

```text
POST
```

Header:

```text
X-SMS-Token: value from production .env
```

Do not write the real token in docs or code.

## Common Problems

### Dubious ownership Git error

This happens when running Git as `anike` inside the `final4810` owned repo.
Prefer:

```bash
sudo -u final4810 bash -lc 'cd /home/finalaccess.com/public_html && git status -sb'
```

### Pull blocked by local changes

Check the changed files:

```bash
git status -sb
git diff -- path/to/file
```

If the local changes are important, commit them or copy them before pulling.
If they are unrelated to the deployment, do not overwrite them.

### 500 error after deploy

Run:

```bash
php artisan optimize:clear
tail -n 100 storage/logs/laravel.log
```

Then check `.env`, database connectivity, file permissions, and whether a
required migration or Composer install was missed.

### Login/session issues

Check:

```bash
php artisan about --only=environment
```

Confirm `APP_URL`, `SESSION_DRIVER`, HTTPS, and storage permissions.

## Documentation To Update After Future Changes

When future deployment or operational details change, update this file and cross-check:

- `README.md`
- `AI_MAINTAINER_GUIDE.md`
- `PROJECT_ROADMAP.md` when roadmap-level plans change
- Server path, host, runtime user, and branch
- Required deploy commands
- Required migration commands
- Cron commands
- Webhook URLs
- Backup and rollback steps
- External device connection notes, including MikroTik/OLT access method, ports, and command names

Also update Markdown docs in the same task whenever a code change adds or changes routes, permissions, migrations, artisan commands, `.env` keys, production operations, or business rules. Do not store real passwords, API keys, database credentials, SMS tokens, or private keys in documentation.

OLT production note:

- OLT polling is read-only. Live data commands should only be `show`/`display` commands.
- HSGQ context commands are limited to CLI navigation (`enable`, `config`/`configure`, `interface epon 1-8`, `exit`) before read-only show commands.
- The refresh job polls selected `pon_ports` one by one to collect all PON ONU status and optical power.
- The app supports legacy SSH OLT access through phpseclib because the HSGQ OLT offers `ssh-rsa`/`ssh-dss` host keys.
- `finalaccess.com` must have network reachability to the OLT management IP (`192.168.10.111:22`) through LAN/VPN/routing; otherwise live polling cannot work from production.
