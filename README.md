# Ultimate Solution ISP Manager

A Laravel 12 application for a computer service shop and internet service provider.

## Features

- Dashboard with customers, income, due, ticket, and stock summary
- Internet package management
- Customer registration with connection ID
- Customer package subscription
- Monthly invoice generation
- Payment collection and due tracking
- Support ticket creation and technician assignment
- Product inventory with stock in/out
- Demo seed data for quick testing

## Local Setup

```bash
composer install
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

Open:

```text
http://127.0.0.1:8000
```

## Main Routes

- `/` dashboard
- `/customers`
- `/packages`
- `/invoices`
- `/payments`
- `/bkash-sms-payments`
- `/tickets`
- `/products`

## bKash SMS Auto Forwarding

এই অ্যাপ bKash থেকে আসা received payment SMS parse করে payment log, customer ledger, invoice payment, duplicate TrxID flag, Ref value এবং extra balance update করতে পারে। Android মোবাইল থেকে SMS auto পাঠানোর জন্য `SmsForwarder` ব্যবহার করুন।

### ১. ডাউনলোড করার পদ্ধতি

প্রথমে আপনার মোবাইলের ব্রাউজার থেকে এই লিঙ্কে যান:

```text
https://github.com/pppscn/SmsForwarder/releases
```

সেখানে সবার উপরে থাকা Latest ভার্সনটির নিচে `Assets` সেকশনে যান।

সেখান থেকে `sms-forwarder-v3.x.x-universal.apk` বা `sms-forwarder-v3.x.x-arm64-v8a.apk` ফাইলটি ডাউনলোড করুন। আপনার ফোন লেটেস্ট হলে `arm64-v8a` ফাইলটি নিন।

### ২. ইনস্টল করা

ডাউনলোড শেষ হলে APK ফাইলটিতে ক্লিক করে ইনস্টল করুন।

ইনস্টল করার সময় Android `Unknown Sources` থেকে permission চাইলে Allow করে দিন।

যেহেতু এটি open source এবং আপনি সরাসরি developer release থেকে নিচ্ছেন, এটি নিরাপদ।

### ৩. চাইনিজ থেকে ইংরেজি করা

অ্যাপটি ওপেন করার পর interface Chinese দেখলে ভয় পাবেন না।

নিচের bar বা navigation থেকে একদম ডানদিকের Settings icon-এ ক্লিক করুন।

নিচের দিকে scroll করে `Language / 语言设置` option খুঁজুন। সাধারণত global বা পৃথিবীর মতো icon থাকে।

সেখানে ক্লিক করে English select করুন। সাথে সাথে পুরো অ্যাপ English হয়ে যাবে।

### ৪. Laravel-এর জন্য Rules Setup

English করার পর মূলত দুটি কাজ করতে হবে।

প্রথমে `Sender` বা `Send Channel` এ গিয়ে Laravel project-এর webhook URL add করুন:

```text
http://192.168.7.246/isp_codex/public/api/bkash/sms
```

Server method:

```text
POST
```

Header:

```text
X-SMS-Token: us-bkash-sms-2026
```

Body JSON:

```json
{
  "sender": "{{from}}",
  "message": "{{msg}}"
}
```

তারপর `Rule` এ গিয়ে কোন SMS forward হবে সেটা বলে দিন।

Recommended rule:

- Sender contains: `bKash`
- Content contains: `You have received`
- Send channel: Laravel webhook channel

সব SMS forward করতে চাইলে `Forward all` mode on রাখতে পারেন, তবে শুধু bKash received payment SMS forward করাই ভালো।

### ৫. SMS Log দেখা

Laravel app-এ login করে Billing menu থেকে `bKash SMS` খুলুন, অথবা এই URL ব্যবহার করুন:

```text
http://localhost/isp_codex/public/bkash-sms-payments
```

এই list-এ দেখা যাবে:

- raw SMS
- amount
- sender number
- Ref value
- TrxID
- duplicate status
- কোন customer match হয়েছে
- কোন invoice/payment/customer balance update হয়েছে

### কেন এটিই সেরা Choice

- No Costs: কোনো monthly charge নেই।
- Local Control: data আপনার server এবং আপনার phone-এর মধ্যেই সীমাবদ্ধ থাকে।
- Battery Efficient: এটি খুব অল্প power খরচ করে চলে।

## Demo Data

The seeder creates:

- Admin user: `admin@example.com`
- Password: `password`
- Two internet packages
- One customer
- One unpaid invoice
- One support ticket
- Two inventory products

Authentication is not enabled yet, so the admin user is prepared for the next phase.

## Important Files

- `AI_MAINTAINER_GUIDE.md`: Read this first before asking another AI to modify billing, bKash SMS, customer status, or MikroTik sync logic.
- `routes/web.php`: All browser routes
- `app/Models`: Database models and relationships
- `app/Http/Controllers`: Page and form logic
- `app/Services/BillingService.php`: Monthly bill generation
- `app/Services/PaymentService.php`: Payment and invoice due update
- `app/Services/InventoryService.php`: Stock in/out handling
- `database/migrations/2026_04_26_000000_create_isp_management_tables.php`: ISP system tables
- `database/seeders/DatabaseSeeder.php`: Demo data
- `resources/views`: Blade admin screens
- `PROJECT_ROADMAP.md`: Longer roadmap and developer documentation

## Verification

```bash
php artisan route:list --except-vendor
php artisan test
```
