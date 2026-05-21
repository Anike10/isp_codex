# Ultimate Solution ISP Manager

A Laravel 12 application for an ISP and computer service business.

## Features

- Dashboard with customers, income, due, tickets, and stock summary
- Customer and internet package management
- MikroTik router management and PPPoE user sync
- OLT ONU live polling with status and optical power inventory
- Monthly invoice generation
- Customer direct payment and due tracking
- bKash SMS payment parsing with duplicate TrxID protection
- Advance balance and payment allocation ledger
- Payment accounts for cash, bKash, Nagad, and bank
- Support ticket management
- Product inventory with stock in/out
- Role and permission based admin access

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

Live site:

```text
https://finalaccess.com
```

Production Laravel root:

```text
/home/finalaccess.com/public_html
```

Read the deployment runbook before updating production:

```text
DEPLOYMENT.md
```

Short version:

```bash
php artisan test
git push origin main
ssh anike@162.4.6.7
sudo -u final4810 bash
cd /home/finalaccess.com/public_html
git pull --ff-only origin main
php artisan optimize:clear
```

Do not commit SSH passwords, `.env` secrets, SMS tokens, or database passwords.

## Main Routes

- `/` dashboard
- `/customers`
- `/packages`
- `/mikrotik-routers`
- `/olt-onus`
- `/olt-onus/{oltOnu}` - ONU detail page and live refresh button
- `/invoices`
- `/payments`
- `/bkash-sms-payments`
- `/payment-accounts`
- `/accounting/ledger`
- `/tickets`
- `/products`

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

Production path for finalaccess.com:

```cron
* * * * * cd /home/finalaccess.com/public_html && php artisan mikrotik:sync-router-users >> /dev/null 2>&1
```

Overdue/grace disable প্রতিদিন রাত ১২:০৫:

```cron
5 0 * * * cd /var/www/isp_codex && php artisan billing:disable-overdue-customers >> /dev/null 2>&1
```

Production path for finalaccess.com:

```cron
5 0 * * * cd /home/finalaccess.com/public_html && php artisan billing:disable-overdue-customers >> /dev/null 2>&1
```

Future CWMP/TR-069 sync প্রতি ৫ মিনিটে:

```cron
*/5 * * * * cd /var/www/isp_codex && php artisan cwmp:sync-devices >> /dev/null 2>&1
```

Production Laravel scheduler ব্যবহার করলে:

```cron
* * * * * cd /var/www/isp_codex && php artisan schedule:run >> /dev/null 2>&1
```

Production path for finalaccess.com:

```cron
* * * * * cd /home/finalaccess.com/public_html && php artisan schedule:run >> /dev/null 2>&1
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

Network requirement:

- The PHP server running this app must be able to reach `192.168.10.111:22`.
- If `finalaccess.com` is hosted outside your LAN and has no VPN/route to the OLT, live polling will fail from production. In that case run the app inside the LAN or connect the production server to the management network through VPN.

## CWMP / TR-069 Note

CWMP/TR-069 support should be implemented through an ACS server such as GenieACS, not directly inside Laravel. Laravel should integrate with the ACS API to list devices, link CPEs to customers, show last inform/status, and trigger safe actions like refresh/reboot.

The full implementation plan, required tables, routes, security notes, MikroTik/CPE setup notes, and acceptance checklist are documented in:

```text
AI_MAINTAINER_GUIDE.md
```

## Important Files

- `DEPLOYMENT.md`: Production deployment, backup, rollback, cron, and finalaccess.com server notes.
- `AI_MAINTAINER_GUIDE.md`: Read this first before asking another AI to modify billing, bKash SMS, customer status, MikroTik sync, or CWMP/TR-069 logic.
- `routes/web.php`: Browser routes
- `routes/api.php`: API/webhook routes
- `routes/console.php`: Artisan commands
- `app/Models`: Database models and relationships
- `app/Http/Controllers`: Page and form logic
- `app/Services/BillingService.php`: Monthly bill generation
- `app/Services/PaymentService.php`: Payment allocation and advance balance logic
- `app/Services/BkashSmsPaymentService.php`: bKash SMS parsing and matching
- `app/Services/MikrotikCustomerSyncService.php`: MikroTik PPPoE sync
- `app/Services/OltSshClient.php`: SSH client for live OLT polling with legacy HSGQ host key support
- `app/Services/OltTelnetClient.php`: Telnet client for live OLT polling
- `app/Services/OltLiveOutputParser.php`: Parses live OLT ONU status and power output
- `resources/views`: Blade admin screens
- `PROJECT_ROADMAP.md`: Longer roadmap and developer documentation

## Documentation Rule

After any code or production change, update the relevant Markdown files in the same work session:

- `README.md`: user-facing features, routes, setup, commands, and operational notes
- `AI_MAINTAINER_GUIDE.md`: architecture, business rules, important files, route/permission rules, limitations, and safe-change notes
- `DEPLOYMENT.md`: production server path, deploy steps, migrations, cache commands, cron, rollback, and finalaccess.com operational details
- `PROJECT_ROADMAP.md`: longer-term plans or module roadmap changes

Always update docs when changing:

- routes, menus, permissions, or URLs
- database migrations or required artisan commands
- payment, billing, bKash SMS, MikroTik, OLT, or customer-status business rules
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
