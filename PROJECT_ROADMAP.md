# Computer & ISP Management System

Developer documentation and roadmap for a Laravel application that manages a computer service/business and an internet service provider operation.

## 1. Project Overview

### What The Software Does

This project is intended to be a Laravel-based management system for:

- Computer sales and service centers
- Internet service providers
- Customer billing
- Internet package management
- Monthly invoice generation
- Payments and due tracking
- Service complaints and support tickets
- Technicians and field work
- Inventory for routers, cables, computers, parts, and accessories

### Main Purpose

The main goal is to help an ISP or computer business manage customers, packages, bills, payments, support, and inventory from one admin panel.

### Core Features

- Customer registration and profile management
- Internet package creation and assignment
- Monthly billing and invoice generation
- Payment collection and due reports
- Support ticket and complaint tracking
- Technician assignment
- Inventory and stock management
- Computer/service product sales
- Admin dashboard with summary reports
- Role-based access for admin, staff, accountant, and technician

## 2. File & Folder Structure Explanation

The current workspace was empty when this documentation was generated. Below is the recommended Laravel structure for this project.

```text
kps_codex/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── CustomerController.php
│   │   │   ├── PackageController.php
│   │   │   ├── InvoiceController.php
│   │   │   ├── PaymentController.php
│   │   │   ├── TicketController.php
│   │   │   ├── InventoryController.php
│   │   │   └── DashboardController.php
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Customer.php
│   │   ├── InternetPackage.php
│   │   ├── Subscription.php
│   │   ├── Invoice.php
│   │   ├── Payment.php
│   │   ├── SupportTicket.php
│   │   ├── Product.php
│   │   └── StockMovement.php
│   ├── Policies/
│   └── Services/
│       ├── BillingService.php
│       ├── PaymentService.php
│       └── InventoryService.php
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── public/
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   ├── dashboard/
│   │   ├── customers/
│   │   ├── packages/
│   │   ├── invoices/
│   │   ├── payments/
│   │   ├── tickets/
│   │   └── inventory/
│   ├── css/
│   └── js/
├── routes/
│   ├── web.php
│   ├── api.php
│   └── console.php
├── storage/
├── tests/
│   ├── Feature/
│   └── Unit/
├── .env
├── artisan
├── composer.json
├── package.json
└── README.md
```

### Folder Purpose

- `app/Models`: Database models and relationships.
- `app/Http/Controllers`: Request handling and page/API logic.
- `app/Http/Requests`: Form validation rules.
- `app/Services`: Business logic such as billing, payment, and stock updates.
- `database/migrations`: Database table definitions.
- `database/seeders`: Demo or default data.
- `resources/views`: Blade templates for the admin panel.
- `routes/web.php`: Web routes for browser pages.
- `routes/api.php`: API routes for mobile apps or external systems.
- `tests`: Automated tests.
- `public`: Public entry point and compiled assets.
- `storage`: Logs, cache, uploaded files, generated invoices.

## 3. Code Architecture

### Recommended Architecture

```text
Browser/Admin Panel
        |
        v
routes/web.php
        |
        v
Controller
        |
        v
Form Request Validation
        |
        v
Service Class
        |
        v
Model / Database
        |
        v
Blade View / JSON Response
```

### Module Connections

- `CustomerController` uses `Customer`, `Subscription`, and `InternetPackage`.
- `PackageController` uses `InternetPackage`.
- `InvoiceController` uses `Invoice`, `Customer`, `Subscription`, and `BillingService`.
- `PaymentController` uses `Payment`, `Invoice`, and `PaymentService`.
- `TicketController` uses `SupportTicket`, `Customer`, and `User`.
- `InventoryController` uses `Product`, `StockMovement`, and `InventoryService`.
- `DashboardController` reads summary data from customers, invoices, payments, tickets, and stock.

### Data Flow Example: Monthly Bill

```text
Admin clicks Generate Bills
        |
BillingService checks active subscriptions
        |
Creates one invoice per active customer
        |
Invoice status becomes unpaid
        |
Dashboard and customer profile show due amount
```

### Data Flow Example: Payment

```text
Staff receives payment
        |
PaymentController validates amount
        |
PaymentService records payment
        |
Invoice paid amount is updated
        |
Invoice status becomes paid or partial
```

## 4. Function & Class Breakdown

### Important Models

#### `Customer`

Represents an ISP or computer service customer.

Important fields:

- `name`
- `phone`
- `email`
- `address`
- `connection_id`
- `status`

Relationships:

- Has many `Subscription`
- Has many `Invoice`
- Has many `Payment`
- Has many `SupportTicket`

#### `InternetPackage`

Represents an internet plan.

Important fields:

- `name`
- `speed`
- `monthly_price`
- `description`
- `status`

Relationships:

- Has many `Subscription`

#### `Subscription`

Connects a customer with an internet package.

Important fields:

- `customer_id`
- `internet_package_id`
- `start_date`
- `end_date`
- `status`

Relationships:

- Belongs to `Customer`
- Belongs to `InternetPackage`

#### `Invoice`

Represents a monthly bill or sales invoice.

Important fields:

- `customer_id`
- `invoice_no`
- `billing_month`
- `subtotal`
- `discount`
- `total`
- `paid_amount`
- `due_amount`
- `status`

Relationships:

- Belongs to `Customer`
- Has many `Payment`

#### `Payment`

Represents money collected from a customer.

Important fields:

- `customer_id`
- `invoice_id`
- `amount`
- `payment_method`
- `payment_date`
- `note`

Relationships:

- Belongs to `Customer`
- Belongs to `Invoice`

#### `SupportTicket`

Represents customer complaint or support work.

Important fields:

- `customer_id`
- `assigned_to`
- `subject`
- `description`
- `priority`
- `status`

Relationships:

- Belongs to `Customer`
- Belongs to technician `User`

#### `Product`

Represents computer parts, routers, cable, or other stock items.

Important fields:

- `name`
- `sku`
- `category`
- `purchase_price`
- `sale_price`
- `stock_quantity`

Relationships:

- Has many `StockMovement`

#### `StockMovement`

Tracks stock in and stock out.

Important fields:

- `product_id`
- `type`
- `quantity`
- `reason`
- `reference_no`

Relationships:

- Belongs to `Product`

### Important Services

#### `BillingService`

Purpose:

- Generate monthly invoices.
- Calculate package price, discount, paid amount, and due amount.
- Prevent duplicate invoice generation for the same customer and month.

Input:

- Billing month
- Active subscriptions

Output:

- Created invoice records

#### `PaymentService`

Purpose:

- Store customer payments.
- Update invoice paid and due amounts.
- Change invoice status.

Input:

- Invoice ID
- Customer ID
- Payment amount
- Payment method

Output:

- Payment record
- Updated invoice

#### `InventoryService`

Purpose:

- Increase or decrease stock.
- Record stock movement history.
- Prevent negative stock.

Input:

- Product ID
- Quantity
- Movement type

Output:

- Updated product stock
- Stock movement record

## 5. Dependency Mapping

### External Libraries

Recommended Laravel dependencies:

- `laravel/framework`: Main Laravel framework.
- `laravel/breeze` or `laravel/jetstream`: Authentication scaffolding.
- `spatie/laravel-permission`: Role and permission management.
- `barryvdh/laravel-dompdf`: PDF invoice generation.
- `maatwebsite/excel`: Excel import/export for customers and reports.
- `intervention/image`: Image upload processing if customer/product photos are needed.

### Frontend Dependencies

Recommended options:

- Blade + Bootstrap or Tailwind CSS for a classic admin panel.
- Alpine.js for small interactions.
- Vite for asset building.

### Internal Dependencies

```text
Customer
├── Subscription
├── Invoice
├── Payment
└── SupportTicket

InternetPackage
└── Subscription

Invoice
└── Payment

Product
└── StockMovement

User
└── SupportTicket assigned technician
```

## 6. Bug Analysis Section

Because no source code exists yet, these are the main risks to avoid during implementation.

### Potential Bugs

- Duplicate monthly invoices for the same customer.
- Payment amount greater than due amount.
- Negative stock after product sale or technician usage.
- Deleted customer breaking invoice or payment history.
- Wrong customer package assigned.
- Ticket assigned to a deleted or inactive technician.
- Date/month mismatch during billing.
- Users accessing pages without proper permission.

### Code Smells To Avoid

- Putting all business logic inside controllers.
- Repeating billing calculation in multiple files.
- Using raw SQL everywhere without need.
- No validation before saving payment, customer, or stock data.
- No database transactions for payment and invoice updates.
- Hard-coded role names scattered across many files.
- No tests for money calculation.

## 7. Improvement Suggestions

### Performance Improvements

- Use pagination on customer, invoice, payment, and ticket lists.
- Add indexes to searchable fields:
  - `customers.phone`
  - `customers.connection_id`
  - `invoices.invoice_no`
  - `invoices.billing_month`
  - `payments.payment_date`
- Use eager loading to avoid N+1 queries:
  - `Customer::with('subscriptions.package', 'invoices')`
- Cache dashboard totals for short periods.
- Run bill generation using queued jobs for large customer lists.

### Code Structure Improvements

- Keep controllers thin.
- Put billing logic in `BillingService`.
- Put payment logic in `PaymentService`.
- Put stock logic in `InventoryService`.
- Use Form Request classes for validation.
- Use policies or permissions for access control.
- Use enums or constants for statuses.

### Security Improvements

- Use Laravel authentication.
- Use role-based permissions.
- Validate every form request.
- Protect all admin routes with auth middleware.
- Escape user-generated output in Blade.
- Use CSRF protection on all forms.
- Store sensitive values in `.env`.
- Avoid exposing debug mode in production.
- Log payment and invoice changes with user ID.

## 8. Debugging Guide

### Common Problem Areas

- Billing generation
- Payment update
- Stock movement
- Customer package assignment
- Permission checks
- Dashboard totals

### Step-by-Step Debugging

#### Billing Issue

1. Check if the customer has an active subscription.
2. Check if the package price is correct.
3. Check if an invoice already exists for the same month.
4. Check `storage/logs/laravel.log`.
5. Check database rows in `subscriptions` and `invoices`.

#### Payment Issue

1. Check if the invoice exists.
2. Check invoice `total`, `paid_amount`, and `due_amount`.
3. Confirm payment amount is not more than due.
4. Check if payment and invoice update run inside a transaction.
5. Check `payments` table for duplicate records.

#### Stock Issue

1. Check current product stock.
2. Check stock movement type: `in` or `out`.
3. Confirm stock-out quantity is not greater than available stock.
4. Check `stock_movements` table.
5. Review any related sale or technician usage record.

#### Login Or Permission Issue

1. Check if the user is active.
2. Check assigned role.
3. Check permission middleware.
4. Check route middleware in `routes/web.php`.
5. Clear Laravel cache:

```bash
php artisan optimize:clear
```

### Useful Laravel Debug Commands

```bash
php artisan route:list
php artisan migrate:status
php artisan optimize:clear
php artisan queue:work
php artisan test
```

## 9. Simple Summary For Other Developers

This is a Laravel admin system for an ISP and computer service business.

The main records are:

- Customers
- Internet packages
- Subscriptions
- Invoices
- Payments
- Support tickets
- Products
- Stock movements
- Users and roles

The most important flow is:

```text
Customer gets internet package
        |
Monthly invoice is generated
        |
Customer makes payment
        |
Due amount is updated
        |
Dashboard shows business status
```

Support flow:

```text
Customer complains
        |
Ticket is created
        |
Technician is assigned
        |
Ticket is resolved
```

Inventory flow:

```text
Product is purchased
        |
Stock increases
        |
Product is sold or used
        |
Stock decreases
```

## Development Roadmap

### Phase 1: Laravel Setup

- Install Laravel.
- Configure `.env`.
- Set up database.
- Install authentication.
- Create base admin layout.

### Phase 2: Core Database

- Create migrations for customers.
- Create migrations for internet packages.
- Create subscriptions table.
- Create invoices and payments tables.
- Create support tickets table.
- Create products and stock movements tables.

### Phase 3: Customer And Package Management

- Customer CRUD.
- Package CRUD.
- Assign package to customer.
- Customer status management.
- Customer search by phone, name, or connection ID.

### Phase 4: Billing And Payments

- Generate monthly invoices.
- Prevent duplicate invoices.
- Payment entry.
- Partial payment support.
- Due report.
- Printable invoice.
- PDF invoice download.

### Phase 5: Support Tickets

- Create complaint ticket.
- Assign technician.
- Track pending, processing, resolved, and closed status.
- Add ticket notes.
- Ticket report.

### Phase 6: Inventory

- Product CRUD.
- Stock in.
- Stock out.
- Low stock alert.
- Router/cable/device usage tracking.

### Phase 7: Dashboard And Reports

- Total customers.
- Active customers.
- Monthly income.
- Total due.
- Open tickets.
- Low stock products.
- Daily collection report.
- Monthly billing report.

### Phase 8: Security And Roles

- Admin role.
- Staff role.
- Accountant role.
- Technician role.
- Permission-based menu.
- Activity logs.

### Phase 9: Testing And Deployment

- Feature tests for billing and payment.
- Unit tests for services.
- Production `.env` setup.
- Database backup plan.
- Deploy to VPS or shared hosting.
- Set up cron job for scheduled billing.

## Suggested Database Tables

```text
users
customers
internet_packages
subscriptions
invoices
payments
support_tickets
products
stock_movements
activity_logs
```

## Suggested Route Groups

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::resource('customers', CustomerController::class);
    Route::resource('packages', PackageController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::resource('payments', PaymentController::class);
    Route::resource('tickets', TicketController::class);
    Route::resource('products', InventoryController::class);

    Route::post('/billing/generate', [InvoiceController::class, 'generateMonthlyBills']);
});
```

## বাংলা সংক্ষিপ্ত ব্যাখ্যা

এই Laravel অ্যাপটি একটি কম্পিউটার ব্যবসা এবং ইন্টারনেট সার্ভিস প্রোভাইডারের জন্য তৈরি হবে।

এখানে অ্যাডমিন বা স্টাফরা করতে পারবে:

- কাস্টমার যোগ করা
- ইন্টারনেট প্যাকেজ তৈরি করা
- কাস্টমারকে প্যাকেজ দেওয়া
- মাসিক বিল তৈরি করা
- পেমেন্ট গ্রহণ করা
- বকেয়া দেখা
- অভিযোগ বা সাপোর্ট টিকিট ম্যানেজ করা
- কম্পিউটার/রাউটার/কেবল/পার্টস স্টক ম্যানেজ করা
- রিপোর্ট দেখা

নতুন ডেভেলপারের জন্য সবচেয়ে গুরুত্বপূর্ণ বিষয়:

- Billing logic controller-এ না রেখে `BillingService`-এ রাখতে হবে।
- Payment update database transaction দিয়ে করতে হবে।
- Stock কখনো negative হতে দেওয়া যাবে না।
- Customer, invoice, payment data delete না করে status দিয়ে inactive করা ভালো।
