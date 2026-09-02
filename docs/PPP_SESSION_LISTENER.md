# PPP Session Event Listener

Data Usage is collected from RouterOS `/ppp/active/listen`. Each native-API
router needs one long-lived Artisan process managed by Supervisor. The worker
opens the listener first, performs one startup reconciliation on a second API
connection, and then writes usage only when RouterOS pushes session events.

REST transport cannot carry a RouterOS event stream. Configure a router with
the binary `api` transport before starting its listener.

## Verify one router

Use the router's database ID:

```bash
php artisan mikrotik:listen-ppp-sessions 1 --once
```

Leave this running long enough to see a PPP disconnect. `--once` makes an API
error exit instead of retrying forever, which is useful before enabling
Supervisor.

## Supervisor

Create one program block per active native-API router. Change the program name,
router ID, paths, and Unix user for each block.

```ini
[program:isp-ppp-listener-1]
command=/usr/bin/php /home/isp.us.com.bd/isp_codex/artisan mikrotik:listen-ppp-sessions 1 --retry=5
directory=/home/isp.us.com.bd/isp_codex
user=ispus3797
autostart=true
autorestart=true
startsecs=5
stopasgroup=true
killasgroup=true
stopwaitsecs=15
redirect_stderr=true
stdout_logfile=/home/isp.us.com.bd/isp_codex/storage/logs/ppp-listener-1.log
stdout_logfile_maxbytes=20MB
stdout_logfile_backups=5
```

After installing or changing the Supervisor file:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl status
```

The legacy `mikrotik:sync-active-macs` command remains available for a manual
reconciliation, but it is not scheduled. Normal usage collection performs no
periodic `/ppp/active` polling.

RouterOS documents that `listen` emits `.dead=yes` when an item disappears and
also cautions that listen does not behave as expected in every menu/build. Test
each router with `--once`; the production worker reconnects after transient
network failures and reconciles sessions once after every reconnect.
