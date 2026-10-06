# MikroTik Reconciliation And Failure Monitoring

The application keeps RouterOS PPPoE secrets aligned with party status and the
package profile stored in the app.

## Scheduled work

- `mikrotik:reconcile-customers` runs every night at `02:30` Asia/Dhaka. It
  checks every app-managed party on every active writable router, including
  inactive parties, compares the expected package/inactive profile with the
  live PPP secret, applies corrections, and verifies the result.
- `mikrotik:retry-failed-syncs` runs daily at `12:30` Asia/Dhaka. It retries
  routers that still have an unresolved sync failure or mismatch.
- The normal interval-based `mikrotik:sync-router-users` command remains in
  place. Failures from direct party sync and router-wide sync are also tracked.

Production must run Laravel's scheduler every minute:

```cron
* * * * * cd /home/isp.us.com.bd/isp_codex && php artisan schedule:run >> /dev/null 2>&1
```

## Failure and mismatch records

- `mikrotik_sync_failures` is append-only and stores every failed attempt with
  its router, optional party, context, error, and attempt time.
- `mikrotik_sync_issues` tracks the current unresolved problem for a
  router/party pair. A resolved issue is retained, and a later failure reopens
  it with a new `first_detected_at` time.
- An unresolved issue appears on the first dashboard page only after it has
  remained unresolved for 24 hours. The dashboard shows up to the oldest 100
  issues, the expected and actual profile, attempts, and last error.

## Deployment

This feature requires the migration that creates both monitoring tables:

```bash
php artisan migrate --force
php artisan optimize:clear
```

Manual verification commands:

```bash
php artisan mikrotik:reconcile-customers
php artisan mikrotik:retry-failed-syncs
php artisan schedule:list
```
