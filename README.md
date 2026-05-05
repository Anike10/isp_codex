# KPS Computer & ISP Manager

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
- `/tickets`
- `/products`

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
