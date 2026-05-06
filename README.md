# Ultimate Solution ISP Manager

A Laravel 12 application for an ISP and computer service business.

## Features

- Dashboard with customers, income, due, tickets, and stock summary
- Customer and internet package management
- MikroTik router management and PPPoE user sync
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

## Main Routes

- `/` dashboard
- `/customers`
- `/packages`
- `/mikrotik-routers`
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

Overdue/grace disable প্রতিদিন রাত ১২:০৫:

```cron
5 0 * * * cd /var/www/isp_codex && php artisan billing:disable-overdue-customers >> /dev/null 2>&1
```

Future CWMP/TR-069 sync প্রতি ৫ মিনিটে:

```cron
*/5 * * * * cd /var/www/isp_codex && php artisan cwmp:sync-devices >> /dev/null 2>&1
```

Production Laravel scheduler ব্যবহার করলে:

```cron
* * * * * cd /var/www/isp_codex && php artisan schedule:run >> /dev/null 2>&1
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

## CWMP / TR-069 Note

CWMP/TR-069 support should be implemented through an ACS server such as GenieACS, not directly inside Laravel. Laravel should integrate with the ACS API to list devices, link CPEs to customers, show last inform/status, and trigger safe actions like refresh/reboot.

The full implementation plan, required tables, routes, security notes, MikroTik/CPE setup notes, and acceptance checklist are documented in:

```text
AI_MAINTAINER_GUIDE.md
```

## Important Files

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
- `resources/views`: Blade admin screens
- `PROJECT_ROADMAP.md`: Longer roadmap and developer documentation

## Verification

Run after changes:

```bash
php artisan route:list --except-vendor
php artisan test
```
