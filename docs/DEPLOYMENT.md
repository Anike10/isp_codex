# Deployment Runbook

This file documents how to update the live Laravel app on **isp.us.com.bd**.

## Mandatory Local/Live Sync Rule

- Never change production before making the same change locally.
- Make every code change locally, commit it, and push it to `origin/main` before deploying it to production.
- Do not create production-only hotfixes or manually edit live application files.
- Before deployment, confirm local `main` is clean and matches `origin/main`.
- After deployment, confirm local, `origin/main`, and production are on the same commit with clean tracked working trees.
- A deployment is not complete while local and production code differ.

Keep passwords, API keys, database credentials, and SSH private keys out of this
repository. Use the server owner, password manager, or approved secure note when
credentials are needed.

## Production Server

```text
Domain: isp.us.com.bd
Proxmox SSH host: 162.4.6.8:2233
SSH user: root
Production VM: 102 (anike-CyberPanel-Hosting)
VM private IP: 192.168.8.252
Laravel root inside VM: /home/isp.us.com.bd/isp_codex
Runtime user inside VM: ispus3797
Panel/server: Proxmox VE 9 host + CyberPanel/OpenLiteSpeed VM
Environment: production
APP_URL: https://isp.us.com.bd
```

Known SSH host key fingerprint:

```text
SHA256:ajsC09Yg/+hgcn2YAETDOYavBgcqxEqQDQcyeYRd33c
```

The fingerprint is for the Proxmox host on port 2233. Never store its root
password in this repository. Obtain it from the owner/approved secret source.

Important state observed on 2026-07-18: the VM checkout contains many
production hotfixes as modified/untracked files. Do not run `git pull`, reset,
checkout, clean, or overwrite the whole tree until those changes have been
reconciled and committed. Use a timestamped backup and deploy only verified
target files when an urgent fix is required.

## What To Deploy

The production app is a Git checkout of:

```text
https://github.com/Anike10/isp_codex.git
branch: main
```

Normal deployment flow:

1. Finish and test locally.
2. Commit the local changes with a detailed Git message that explains what
   changed, why it changed, and how it was verified.
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

Windows PowerShell with PuTTY (connects to the Proxmox host):

```powershell
& 'C:\Program Files\PuTTY\plink.exe' -P 2233 -ssh root@162.4.6.8 -hostkey 'SHA256:ajsC09Yg/+hgcn2YAETDOYavBgcqxEqQDQcyeYRd33c'
```

OpenSSH:

```bash
ssh -p 2233 root@162.4.6.8
```

The Laravel app is inside VM 102, not on the Proxmox host filesystem. Run app
commands through the QEMU guest agent as the site runtime user:

```bash
qm status 102
qm guest exec 102 -- runuser -u ispus3797 -- bash -lc 'cd /home/isp.us.com.bd/isp_codex && php artisan about --only=environment'
```

## Check Server State Before Pulling

Always check for VM-local edits before updating:

```bash
qm guest exec 102 -- runuser -u ispus3797 -- bash -lc 'cd /home/isp.us.com.bd/isp_codex && git status -sb && git log --oneline -3 && git remote -v'
```

If `git status` shows modified files, do not discard them blindly. Decide whether
they are expected server-only files, old hotfixes, or changes that must be
committed first.

Known state seen on 2026-07-18:

```text
The checkout had numerous modified customer, billing, MikroTik, OLT, layout,
and route files plus untracked MikroTik import controllers/models/views and
migrations. Inspect the live list again before every deployment; do not assume
the exact list is unchanged.
```

These are live operational changes and must not be reverted without review.

## Recommended Deploy Command

Normal Git pull is currently paused until the dirty VM checkout is reconciled.
After reconciliation, the intended command is:

```bash
qm guest exec 102 -- runuser -u ispus3797 -- bash -lc 'cd /home/isp.us.com.bd/isp_codex && git pull --ff-only origin main && php artisan optimize:clear'
```

Use `--ff-only` so the deployment stops instead of creating a merge commit on
the server.

If migrations are included in the new commit, run:

```bash
php artisan migrate --force
php artisan optimize:clear
```

Migration `2026_08_28_000001_add_view_unmanaged_router_users_permission.php`
adds the `view_unmanaged_router_users` permission and grants it to the admin
role. The related `mikrotik:import-secrets` command refreshes imported PPPoE
secrets from all active routers, and the Laravel scheduler runs it every three
hours. Run `php artisan migrate --force` before verifying the dashboard or
`/router-users`.

Party-specific package pricing requires migrations
`2026_08_28_000007_add_custom_price_to_subscriptions.php` and
`2026_08_28_000008_add_special_package_price_permission.php`. They add the
nullable subscription price override and grant
`set_special_package_price` to the admin role. Because this changes billing,
create both code and database backups, run `php artisan migrate --force`, then
verify the party-list price form, a service-bill amount, and `/router-users`
batch import before completing deployment.

Payment-account ownership and super-admin deployment requires migrations
`2026_08_28_000003` through `2026_08_28_000006`. They add the protected
`users.is_super_admin` flag, payment-account owners and delegated operators,
optional balance limits, and auditable office deposits. Create and verify a
database backup first, then run `php artisan migrate --force` before opening
payment forms. Verify `/users`, `/payment-accounts`, `/payment-account-access`,
and an owner deposit flow. The recovery command is `php artisan
user:super-admin user@example.com`; use `--revoke` only when another super admin
will remain.

The multi-router customer assignment feature requires migration
`2026_08_12_000001_create_customer_mikrotik_router_table.php`. It creates the
many-to-many target table and backfills every existing non-null
`customers.mikrotik_router_id`. After deployment, run `php artisan migrate
--force` before opening customer details or running MikroTik sync.

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

Optional fast OLT row refresh can use SNMP before falling back to CLI. To enable
it on production:

```bash
php -m | grep -i snmp
```

If SNMP is missing, install/enable the PHP SNMP extension for the production PHP
version, restart the web runtime, then configure each OLT in the app with:

```text
SNMP enabled: yes
Version: 2c unless the device only supports v1
Community: from the approved secure source, never from Git
Status OID template: vendor-specific, can use {pon_port}, {onu_id}
Power OID template: vendor-specific, can use {pon_port}, {onu_id}
Power divisor: use 10 when a raw value like -238 means -23.8 dBm
```

Run `php artisan migrate --force` after deploying the SNMP polling migration.

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
& 'C:\Program Files\PuTTY\plink.exe' -P 2233 -ssh root@162.4.6.8 -hostkey 'SHA256:ajsC09Yg/+hgcn2YAETDOYavBgcqxEqQDQcyeYRd33c' "qm guest exec 102 -- runuser -u ispus3797 -- bash -lc 'cd /home/isp.us.com.bd/isp_codex && git pull --ff-only origin main && php artisan optimize:clear'"
```

If the server asks for a sudo password, enter it interactively. Do not place the
password in committed scripts or documentation.

## Backups Before Risky Changes

For simple CSS/view changes, a Git commit is usually enough rollback protection.
Before database migrations, large refactors, or payment/billing changes, create
a server backup.

Code backup:

```bash
qm guest exec 102 -- bash -lc 'tar -czf /home/isp.us.com.bd/isp_codex/storage/app/deploy_backups/isp_codex_$(date +%Y%m%d_%H%M%S).tgz -C /home/isp.us.com.bd isp_codex'
```

Database backup from the app:

```bash
qm guest exec 102 -- runuser -u ispus3797 -- bash -lc 'cd /home/isp.us.com.bd/isp_codex && php artisan db:show'
```

Use the app's `/backup/database` route when a logged-in admin backup is enough.
For large databases, prefer `mysqldump` with credentials from `.env`.

## Verify After Deploy

Server checks:

```bash
qm guest exec 102 -- runuser -u ispus3797 -- bash -lc 'cd /home/isp.us.com.bd/isp_codex && git status -sb && git log --oneline -1 && php artisan about --only=environment && php artisan route:list --except-vendor'
```

HTTP check:

```bash
curl -I -L https://isp.us.com.bd
```

If the local machine does not trust the certificate chain, this command may fail
with a certificate warning. For a quick status-only check:

```bash
curl -k -I -L https://isp.us.com.bd
```

Expected behavior for a guest request:

```text
https://isp.us.com.bd -> 302 redirect to /login -> 200 OK
```

App log check:

```bash
tail -n 100 storage/logs/laravel.log
```

## Rollback

If the deployment was a normal fast-forward pull and no migration/data change is
involved, rollback to the previous commit:

```bash
qm guest exec 102 -- runuser -u ispus3797 -- bash -lc 'cd /home/isp.us.com.bd/isp_codex && git log --oneline -5'
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
ispus3797:ispus3797
```

If files are uploaded manually, fix ownership:

```bash
qm guest exec 102 -- chown -R ispus3797:ispus3797 /home/isp.us.com.bd/isp_codex
```

Writable Laravel directories:

```bash
storage
bootstrap/cache
```

If cache/log writes fail:

```bash
chown -R ispus3797:ispus3797 storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

## Cron / Scheduled Commands

Production Laravel root:

```text
/home/isp.us.com.bd/isp_codex
```

Recommended cron entries use this path:

```cron
* * * * * cd /home/isp.us.com.bd/isp_codex && php artisan mikrotik:sync-router-users >> /dev/null 2>&1
* * * * * cd /home/isp.us.com.bd/isp_codex && php artisan schedule:run >> /dev/null 2>&1
```

The Laravel scheduler runs `billing:disable-overdue-customers` hourly only
inside the configurable Organization auto-disable window (default
`12:00-17:00`). The command also enforces the window for legacy direct cron
calls; use `--force` only for an intentional manual run outside it.

Confirm cron is installed for the correct user before changing it:

```bash
crontab -l
qm guest exec 102 -- runuser -u ispus3797 -- crontab -l
```

## Production SMS Webhook

SmsForwarder production URL:

```text
https://isp.us.com.bd/api/bkash/sms
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

This happens when running Git as VM root instead of the `ispus3797` site user.
Prefer:

```bash
qm guest exec 102 -- runuser -u ispus3797 -- bash -lc 'cd /home/isp.us.com.bd/isp_codex && git status -sb'
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

- `../README.md`
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

## Organization-based printing and audit history

- Migration `2026_07_16_000001_create_organizations_and_print_logs.php` creates the organization master list and immutable print-event records.
- Billing > Organizations manages multiple print identities and the default organization.
- Each organization can control whether `Print without signature` starts selected, whether the Organization selector is visible on print previews, and whether its saved bank account details appear on invoices.
- Every supported print preview lets the operator select an active organization. The audit row is created only when the preview's Print button is pressed, before the browser print dialog opens.
- Billing > Print History shows all document, organization, operator, time, and IP records. Each invoice detail page also shows its own print history.
- Production deployment must run `php artisan migrate --force` and `php artisan optimize:clear` before verifying invoice, quotation, challan, payment voucher, thermal voucher, and expense voucher printing.

OLT production note:

- OLT polling is read-only. Live data commands should only be `show`/`display` commands.
- HSGQ context commands are limited to CLI navigation (`enable`, `config`/`configure`, `interface epon 1-8`, `exit`) before read-only show commands.
- The refresh job polls selected `pon_ports` one by one to collect all PON ONU status and optical power.
- The app supports legacy SSH OLT access through phpseclib because the HSGQ OLT offers `ssh-rsa`/`ssh-dss` host keys.
- Saving an active OLT from `Edit OLT` now performs a login-only connection test. It updates the connection badge without requiring `Save OLT Config` and does not send polling or configuration commands.
- HSGQ EPON deny-list authorization uses OLT automatic ONU-ID assignment because blacklist row numbers are not guaranteed free ONU IDs.
- `isp.us.com.bd` must have network reachability to the OLT management IP (`192.168.10.111:22`) through LAN/VPN/routing; otherwise live polling cannot work from production.
