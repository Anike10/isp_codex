# Ultimate Solution ISP Manager

A Laravel 12 application for an ISP and computer service business.

## Features

- Dashboard with customers, income, due, tickets, and stock summary
- Customer and internet package management
- Product-only customers can be saved without an ISP Connection ID
- MikroTik router management and PPPoE user sync, with visible credential/network/API-port diagnostics and autofill-safe RouterOS credential forms
- OLT ONU/ONT live polling with status, optical power, profile-command mismatch repair, PON-wise cached counts, timestamped running-config backup downloads, non-destructive refresh-error clearing, and safe OLT deletion
- Full Power/VLAN/MAC polling runs as a background process with stage-based percentage progress and duplicate-run protection; fast status refresh remains synchronous
- ONU/ONT rows show power beside online/offline status with `Update Now`, plus a separate Ethernet-port column. The selected port is green when enabled, red when disabled, and uses one state-aware Enable/Disable toggle; the HSGQ GPON profile saves `ont port attribute {onu_id} eth {port} admin-status {state}` to the OLT configuration
- HSGQ EPON VLAN editing supports both tagged VLAN and restoring `Transparent` mode. Transparent writes `port-vlan {port} mode transparent`, saves the OLT config, and clears the cached configured VLAN number without rewriting learned traffic MAC VLANs.
- ONU Ethernet controls no longer assume eight ports. Full refresh reads GPON `show ont-capability` port counts, while EPON uses the physical ports returned by each ONU's `show port-vlan`; explicitly disabled ports remain red and all other admin-default ports are shown enabled.
- HSGQ EPON Ethernet admin control uses the firmware-specific ONU-context commands `port-shutdown {port}` and `no port-shutdown {port}`. The row form submits with JSON, updates its button/state in place, and does not create a page flash message.
- ONU note controls can append a timestamped cached laser reading to one row or every ONU/ONT row (`YYYY-MM-DD HH:MM:SS | Laser: -13.58 dBm`) while preserving existing note text. Name, description, and Ethernet-port form saves restore the OLT list scroll position; note, VLAN, and single-row refresh actions remain inline/AJAX.
- FTTX network map with reciprocal equipment links, drag/list linking, Fiber/Copper media, custom colors, and parallel link rendering
- Monthly invoice generation
- Separate quotation workflow with invoice-style entry, no accounting impact, and one-click conversion to a draft invoice
- Quotation, invoice, purchase, and stock flows enforce serial-tracked quantity as `serial count + serial-less quantity = line quantity`
- Invoice copy-for-next-month creates a safe draft copy without duplicating stock product links or serial assignments
- Customer direct payment and due tracking
- bKash SMS payment parsing with duplicate TrxID protection
- Advance balance and payment allocation ledger
- Payments page includes invoice payments and direct advance collections, with
  entry operator/time and a clear invoice-versus-advance breakdown.
- Payment account and cash ledgers use database-level merged pagination and include payment credits, direct advance receipts, expense debits, and auditable deposits to the office without double-counting payment remainders. Non-cash accounts support an owner, delegated operators, and an optional live-balance collection limit.
- Party/accounting ledger rows show a serial number and business date without a repeated Reference column. Internal ordering still uses the full date/time so serial numbers and running balances remain deterministic. A selected party's name stays in the report header instead of repeating in every row. The ledger supports pagination with visible row counts and a route-specific default rows-per-page setting. Its separate A4 portrait print report supports organization selection, Black & white or Color output, zebra-striped rows, an inclusive date range, all filtered rows, and automatic multi-page table printing.
- Edit history/audit snapshots for invoices, quotations, parties, payments, roles, permissions, and other tracked operator-editable records through the shared `record_versions` table, including invoice finalization changes
- Payment accounts for cash, bKash, Nagad, and bank
- Support ticket management
- Multi-warehouse product inventory with an invoice-style multi-item `Inventory > In-house Use` entry page (writable product lookup, quantity, editable unit value, line total, serials, note, and private approval scan/PDF), separate employee/value/used-stock/history reports, partial returns, and isolated returned-used stock
- Purchase bills support a private vendor bill/invoice image or PDF; draft edits can preserve the current copy or securely replace it
- Sale-return credit respects invoice-level discount/VAT, settles the source invoice due first, and only the excess becomes customer advance; all later cash/advance settlement keeps that return credit in the due calculation, and a fully returned unpaid invoice is marked `returned`
- Vehicle & Fleet Management with vehicle status/mileage, Driver/Helper/Supervisor duty history, date/mileage maintenance schedules, itemized trip expenses, and filtered fleet reports (see `FLEET_MANAGEMENT.md`)
- Role and permission based admin access, with a protected super-admin tier for global access and payment-account ownership/delegation management

## Local Setup

```bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Open:

```text
http://127.0.0.1:8000
```

XAMPP/local public URL example:

```text
http://localhost/isp_codex/public
```

## Production Deployment

Canonical live site:

```text
https://isp.us.com.bd
```

Production Laravel root:

```text
/home/isp.us.com.bd/isp_codex (inside Proxmox VM 102)
```

Read the deployment runbook before updating production:

```text
DEPLOYMENT.md
```

Short version:

```bash
php artisan test
git push origin main
ssh root@162.4.6.8
qm status 102
qm guest exec 102 -- runuser -u ispus3797 -- bash -lc 'cd /home/isp.us.com.bd/isp_codex && git status -sb'
```

The VM checkout currently contains production-local hotfixes. Read
`DEPLOYMENT.md`; do not run a blind pull/reset/clean until they are reconciled.

Do not commit SSH passwords, `.env` secrets, SMS tokens, or database passwords.

## Main Routes

- `/` dashboard
- `/customers`
- `/packages` and `/packages/{package}/edit`
- `/mikrotik-routers`
- `/olt-onus`
- `/olt-onus/{oltOnu}` - ONU detail page and live refresh button
- `/invoices`
- `/quotations`
- `/payments`
- Invoice detail pages show the creator user and exact creation time, and include the paginated `Edit History` section backed by JSON snapshots in `record_versions` (or a clear empty-history message).
- `/bkash-sms-payments`
- `/payment-accounts`
- `/accounting/ledger`
- `/tickets`
- `/products`
- `/purchase-bills`

## bKash SMS Auto Forwarding

এই অ্যাপ bKash থেকে আসা received payment SMS parse করে payment log, customer match, invoice payment, duplicate TrxID flag, Ref value, advance balance এবং ledger update করতে পারে।

Android মোবাইল থেকে SMS auto পাঠানোর জন্য `SmsForwarder` ব্যবহার করুন।

### ১. SmsForwarder ডাউনলোড

মোবাইলের ব্রাউজার থেকে এই লিঙ্কে যান:

```text
https://github.com/pppscn/SmsForwarder/releases
```

তারপর:

1. সবার উপরের `Latest` release খুলুন।
2. `Assets` section খুঁজুন।
3. এই ফাইলগুলোর যেকোনো একটি ডাউনলোড করুন:
   - `sms-forwarder-v3.x.x-universal.apk`
   - `sms-forwarder-v3.x.x-arm64-v8a.apk`
4. আপনার ফোন নতুন হলে সাধারণত `arm64-v8a` ভালো। না বুঝলে `universal` নিন।

### ২. ইনস্টল

1. ডাউনলোড শেষ হলে APK ফাইলে চাপ দিন।
2. Android `Unknown Sources` permission চাইলে allow করুন।
3. ইনস্টল শেষ হলে app open করুন।

### ৩. ভাষা English করা

App open করার পর Chinese interface দেখলে:

1. নিচের navigation bar থেকে ডান পাশের `Settings` icon চাপুন।
2. `Language / 语言设置` খুঁজুন।
3. `English` select করুন।

### ৪. Laravel Webhook URL

SmsForwarder-এ `Sender` বা `Send Channel` তৈরি করুন।

Local network example:

```text
http://192.168.7.246/isp_codex/public/api/bkash/sms
```

Production example:

```text
https://your-domain.com/api/bkash/sms
```

Method:

```text
POST
```

Header:

```text
X-SMS-Token: us-bkash-sms-2026
```

Recommended JSON body:

```json
{
  "sender": "{{from}}",
  "message": "{{msg}}",
  "device_name": "{{device_name}}"
}
```

যদি আপনার SmsForwarder version-এ `{{device_name}}` variable না থাকে, শুধু `sender` এবং `message` দিলেও চলবে। তখন app raw forwarded SMS থেকে device name বের করার চেষ্টা করবে।

### ৫. Forward Rule

SmsForwarder-এ rule তৈরি করুন:

- Sender contains: `bKash`
- Content contains: `You have received`
- Send channel: Laravel webhook channel

সব SMS forward না করাই ভালো। শুধু bKash received payment SMS forward করুন।

### ৬. Device Account Rule

bKash SMS-এর `from number` customer/payer number হিসেবে থাকবে। কিন্তু `PaymentAccount` হবে যে মোবাইল SMS forward করেছে সেই device নামে।

Example:

```text
Anike Redmi
```

তাহলে account হবে:

```text
payment_method: bkash
account_name: Anike Redmi
account_number: sms-device:anike-redmi
entry_by: Anike Redmi
entry_by_type: sms_device
```

অন্য মোবাইল থেকে SMS এলে সেই মোবাইলের নাম entry হবে।

### ৭. SMS Log দেখা

Laravel app-এ login করে Billing menu থেকে `bKash SMS` খুলুন, অথবা:

```text
http://localhost/isp_codex/public/bkash-sms-payments
```

এখানে দেখা যাবে:

- raw SMS
- amount
- from number
- Ref value
- TrxID
- duplicate status
- কোন customer match হয়েছে
- কোন invoice/payment/customer balance update হয়েছে

## Windows Task Scheduler Setup

Windows/XAMPP-এ নিয়মিত artisan command চালানোর জন্য Task Scheduler ব্যবহার করুন।

### ১. MikroTik PPPoE Sync Task

এই command router interval অনুযায়ী PPPoE user/profile sync করবে:

```bat
cd /d D:\xampp\htdocs\isp_codex && php artisan mikrotik:sync-router-users
```

Customer MikroTik assignment is many-to-many. On a customer details page,
`MikroTik targets` can save and immediately sync the same PPPoE user to one or
more selected active routers. Customer service status can be temporarily moved
inactive or active in either direction without changing saved validity/grace
data; the customer and latest subscription change together and MikroTik sync
applies the matching inactive or package profile.

Users with the `view_unmanaged_router_users` permission can open
`/router-users` or use the dashboard panel to review PPPoE secrets that exist on
active routers but have no matching app party. `php artisan
mikrotik:import-secrets` refreshes the imported secret snapshot; the Laravel
scheduler runs this refresh every three hours. Selected unmanaged users can be
created as parties from either screen. A party marked as a special ISP customer
is kept active and is synced to its service profile instead of the inactive
profile.

Setup:

1. Start menu থেকে `Task Scheduler` খুলুন।
2. ডান পাশে `Create Task` চাপুন।
3. `General` tab:
   - Name: `ISP MikroTik Sync`
   - `Run whether user is logged on or not` select করতে পারেন।
   - `Run with highest privileges` tick দিন।
4. `Triggers` tab:
   - `New`
   - Begin the task: `On a schedule`
   - Settings: `Daily`
   - Advanced settings:
     - `Repeat task every`: `1 minute`
     - `for a duration of`: `Indefinitely`
   - Enabled tick দিন।
5. `Actions` tab:
   - `New`
   - Action: `Start a program`
   - Program/script:

```text
C:\Windows\System32\cmd.exe
```

Add arguments:

```text
/c "cd /d D:\xampp\htdocs\isp_codex && php artisan mikrotik:sync-router-users"
```

6. Save করুন।
7. Task-এর উপর right click করে `Run` চাপুন।
8. `Last Run Result` `0x0` হলে command সফলভাবে চলেছে।

### ২. Overdue/Grace Disable Task

এই command overdue customer inactive করবে এবং grace period শেষ হলে line inactive করবে:

```bat
cd /d D:\xampp\htdocs\isp_codex && php artisan billing:disable-overdue-customers
```

Task Scheduler setup:

- Name: `ISP Billing Disable Overdue`
- Trigger: Daily
- Time: `12:05 AM` বা `01:00 AM`
- Program/script:

```text
C:\Windows\System32\cmd.exe
```

Add arguments:

```text
/c "cd /d D:\xampp\htdocs\isp_codex && php artisan billing:disable-overdue-customers"
```

### ৩. Future CWMP/TR-069 Task

CWMP/TR-069 module implement করার পর এই command লাগবে:

```bat
cd /d D:\xampp\htdocs\isp_codex && php artisan cwmp:sync-devices
```

Suggested interval:

```text
Every 5 minutes
```

এটি এখনই চালাবেন না, কারণ CWMP module এখনো future plan হিসেবে documented আছে।

## Linux Cron Setup

Linux server হলে crontab ব্যবহার করুন।

Crontab edit:

```bash
crontab -e
```

MikroTik sync প্রতি মিনিটে:

```cron
* * * * * cd /var/www/isp_codex && php artisan mikrotik:sync-router-users >> /dev/null 2>&1
```

Production path for isp.us.com.bd:

```cron
* * * * * cd /home/isp.us.com.bd/isp_codex && php artisan mikrotik:sync-router-users >> /dev/null 2>&1
```

Overdue/grace disable প্রতিদিন রাত ১২:০৫:

```cron
5 0 * * * cd /var/www/isp_codex && php artisan billing:disable-overdue-customers >> /dev/null 2>&1
```

Production path for isp.us.com.bd:

```cron
5 0 * * * cd /home/isp.us.com.bd/isp_codex && php artisan billing:disable-overdue-customers >> /dev/null 2>&1
```

Future CWMP/TR-069 sync প্রতি ৫ মিনিটে:

```cron
*/5 * * * * cd /var/www/isp_codex && php artisan cwmp:sync-devices >> /dev/null 2>&1
```

Production Laravel scheduler ব্যবহার করলে:

```cron
* * * * * cd /var/www/isp_codex && php artisan schedule:run >> /dev/null 2>&1
```

Production path for isp.us.com.bd:

```cron
* * * * * cd /home/isp.us.com.bd/isp_codex && php artisan schedule:run >> /dev/null 2>&1
```

তারপর commands `routes/console.php` বা scheduler config-এ schedule করতে হবে।

## Important Commands

```bash
php artisan migrate
php artisan route:list --except-vendor
php artisan test
php artisan mikrotik:sync-router-users --force
php artisan billing:disable-overdue-customers
```

## OLT ONU Live Power

Use this when you need to see HSGQ OLT ONU status and optical power in the software:

```text
/olt-onus
```

Add the OLT from:

```text
/olt-onus/olts/create
```

Then click `Refresh Live Data`. The app connects to the OLT over read-only SSH
or Telnet and runs the configured live commands to collect:

- PON/ONU ID
- MAC address when present in command output
- ONU status
- RX optical power in dBm
- distance when present in command output
- raw live output per ONU for troubleshooting

Recommended read context and live commands for the current HSGQ OLT:

```text
Read Context Commands:
enable
config

PON Ports To Poll:
1,2,3,4,5,6,7,8

ONU Status/List Command:
show onu-info all

ONU Optical Power Command:
show optical-info
```

Recommended settings for the current HSGQ OLT:

```text
Host/IP: 192.168.10.111
Access Method: SSH for general polling; EPON deny/add uses Telnet fallback
Port: 22
Username: isp_app
Password: from the secure credential source
```

The SSH client allows legacy OLT host key algorithms like `ssh-rsa` and
`ssh-dss`, matching this manual command:

```bash
ssh -oHostKeyAlgorithms=+ssh-rsa -oPubkeyAcceptedAlgorithms=+ssh-rsa isp_app@192.168.10.111
```

Safety rule:

- The app only allows live data commands that start with `show` or `display`.
- Read context commands are separately whitelisted to CLI navigation only: `enable`, `config`/`configure`, `interface epon 1-8`, and `exit`.
- Commands containing `set`, `add`, `delete`, `bind`, `save`, `reboot`, `reset`, and similar write/change words are blocked before connecting.
- Do not put configuration-changing commands in OLT command fields.
- The app polls the selected PON ports one by one and sends `interface epon N` before the two show commands, so all PON ONU records can be refreshed from live OLT output.

EPON deny-list note:

- US_EPON does not expose the deny list through `show black-onu all`.
- Use `show blacklist onu-info all` inside each `interface epon N` context.
- The deny-list page scans all configured EPON ports in one OLT session for speed.
- For this EPON firmware, SSH command entry can lose spaces (`bind-onu 0 mac ...` becomes `bind-onu 0mac...`), so EPON deny-list reads and ONU add/write commands use Telnet port `23`.
- Adding from the deny-list first removes the MAC with `blacklist delete mac {mac}`, then binds the ONU and writes VLAN/name/description.
- HSGQ blacklist row numbers such as `7/1` are deny-list sequence values, not guaranteed free ONU IDs. `Allow ONU` submits ID `0`, lets the OLT assign the next free ID, reads the assigned ID back, then writes the selected VLAN.
- The deny-list `Delete` action only runs `blacklist delete mac {mac}` in the selected EPON PON context and saves the OLT config; it does not bind or authorize the ONU.

OLT connection status note:

- `Edit OLT` uses `Update & Test Connection`. Saving an active OLT immediately performs a login-only SSH/Telnet test; it does not poll ONUs or write OLT configuration.
- A successful edit test, read, utility action, or write clears stale connection errors and updates the connected time. Authentication, refused connection, timeout, and command/action failures have separate operator-facing messages.
- `Save OLT Config` is only for permanently saving changes on the OLT and is not required to make the App show `Connected` after editing credentials.

EPON refresh speed note:

- EPON `Refresh Live Data` uses a fast status-only path.
- It reads `show onu-info all` for each configured PON and skips slower optical, alarm, VLAN-detail, and global MAC polling.
- EPON `Full Power/VLAN Refresh` is intentionally separate from fast refresh. Use the PON selector beside the button when you need fresh optical power for a single PON; scanning all EPON ports must run one optical table per PON and can take tens of seconds on this firmware.
- EPON full refresh skips per-ONU alarm polling. It updates power from the OLT optical table, reads the fast global `show mac-address epon all` table for learned MACs, and refreshes every ONU VLAN with `show port-vlan`. It reconnects between PONs to avoid the OLT/Windows long-session disconnect.
- Refresh flash messages include elapsed OLT query time, e.g. `370 live ONU record(s) refreshed from US_EPON in 3.25 seconds`.
- GPON `Refresh Live Data` reads global `show ont-info all` from config context. Full Power/VLAN refresh keeps optical and service-port polling but skips slow MAC polling; this keeps US_GPON Power/VLAN refresh under about one second while preserving the full ONT count.
- GPON learned MAC refresh is a separate per-PON action. Use `MAC Refresh` with the PON selector; it runs `show mac-address port gpon {pon}` instead of the slow global `show mac-address all`.
- `OltTelnetClient` handles `--More--` pagination and uses non-blocking socket reads so large OLT outputs return when the prompt appears instead of waiting for socket timeout.
- OLT list pages select only the columns needed for the table and default to 200 rows per page for faster browser rendering.
- ONU detail pages preview large raw output instead of rendering the full text into the page.
- A small timing badge appears in the bottom-right corner showing server render time and browser load time.

Network requirement:

- The PHP server running this app must be able to reach `192.168.10.111:22`.
- If `isp.us.com.bd` is hosted outside your LAN and has no VPN/route to the OLT, live polling will fail from production. In that case run the app inside the LAN or connect the production server to the management network through VPN.

## CWMP / TR-069 Note

CWMP/TR-069 support should be implemented through an ACS server such as GenieACS, not directly inside Laravel. Laravel should integrate with the ACS API to list devices, link CPEs to customers, show last inform/status, and trigger safe actions like refresh/reboot.

The full implementation plan, required tables, routes, security notes, MikroTik/CPE setup notes, and acceptance checklist are documented in:

```text
AI_MAINTAINER_GUIDE.md
```

## Important Files

- `DEPLOYMENT.md`: Production deployment, backup, rollback, cron, and isp.us.com.bd server notes.
- `AI_MAINTAINER_GUIDE.md`: Read this first before asking another AI to modify billing, bKash SMS, customer status, MikroTik sync, or CWMP/TR-069 logic.
- `routes/web.php`: Browser routes
- `routes/api.php`: API/webhook routes
- `routes/console.php`: Artisan commands
- `app/Models`: Database models and relationships
- `app/Http/Controllers`: Page and form logic
- `app/Services/BillingService.php`: Monthly bill generation
- `app/Services/PaymentService.php`: Payment allocation and advance balance logic
- `app/Services/RecordVersionService.php`: Full old/new snapshots for complex edits such as invoices, quotations, and parties
- `app/Observers/RecordVersionObserver.php`: Attribute-level edit history for tracked models
- `app/Services/BkashSmsPaymentService.php`: bKash SMS parsing and matching
- `app/Services/MikrotikCustomerSyncService.php`: MikroTik PPPoE sync
- `app/Services/OltSshClient.php`: SSH client for live OLT polling with legacy HSGQ host key support
- `app/Services/OltTelnetClient.php`: Telnet client for live OLT polling
- `app/Services/OltLiveOutputParser.php`: Parses live OLT ONU status and power output
- `resources/views`: Blade admin screens
- `resources/views/partials/record_versions.blade.php`: Full-width old-version preview UI for edit history
- `PROJECT_ROADMAP.md`: Longer roadmap and developer documentation

## Documentation Rule

After any code or production change, update the relevant Markdown files in the same work session:

- `README.md`: user-facing features, routes, setup, commands, and operational notes
- `AI_MAINTAINER_GUIDE.md`: architecture, business rules, important files, route/permission rules, limitations, and safe-change notes
- `DEPLOYMENT.md`: production server path, deploy steps, migrations, cache commands, cron, rollback, and isp.us.com.bd operational details
- `PROJECT_ROADMAP.md`: longer-term plans or module roadmap changes

Always update docs when changing:

- routes, menus, permissions, or URLs
- database migrations or required artisan commands
- payment, billing, bKash SMS, MikroTik, OLT, or customer-status business rules
- audit/version-history behavior, including old snapshot structure and operator history preview UI
- production deployment steps or server paths
- `.env` keys, external device credentials, webhook URLs, cron jobs, or scheduler commands
- known limitations, troubleshooting steps, or rollback process

Never write real passwords, API keys, database credentials, SMS tokens, or private keys in Markdown files.

## Verification

Run after changes:

```bash
php artisan route:list --except-vendor
php artisan test
```
