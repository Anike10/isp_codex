# AI Maintainer Guide

This guide is for another AI agent or developer who needs to update this Laravel project safely.

## Project Snapshot

This is a Laravel 12 app for an ISP/computer-service business named **Ultimate Solution**.

Main capabilities:

- Login-protected admin system
- User, role, and permission management
- Customers and internet packages
- Product/service invoices with draft/final workflow
- Printable bill, quotation, and delivery challan
- Monthly service bill generation from active subscriptions
- Payment collection by cash, bKash, Nagad, and bank
- Payment account balances and ledger
- Inventory products and stock movements
- Support tickets
- Database backup download

Default admin login after migrations:

```text
Email: admin@example.com
Password: password
```

Change this password after first login.

## Tech Stack

- PHP 8.2+
- Laravel 12
- Blade views, no frontend build dependency for current UI
- MySQL via `.env`
- PHPUnit for basic tests

Common commands:

```bash
php artisan migrate
php artisan route:list
php artisan test
php artisan serve
```

## Important Files

- `routes/web.php`: all browser routes and permission middleware
- `resources/views/layouts/app.blade.php`: main authenticated layout and sidebar
- `resources/views/auth/login.blade.php`: login page
- `app/Http/Middleware/EnsureUserHasPermission.php`: permission middleware
- `app/Models/User.php`: user permissions and roles helper logic
- `app/Models/Role.php`, `app/Models/Permission.php`: access control models
- `app/Http/Controllers/UserController.php`: user creation/editing
- `app/Http/Controllers/RoleController.php`: role creation/editing
- `app/Http/Controllers/DatabaseBackupController.php`: SQL backup download
- `app/Http/Controllers/InvoiceController.php`: invoice create/edit/final/print routes
- `resources/views/invoices/create.blade.php`: create and edit invoice form
- `resources/views/invoices/show.blade.php`: invoice details and final button
- `resources/views/invoices/challan.blade.php`: printable bill
- `resources/views/invoices/quotation.blade.php`: printable quotation
- `resources/views/invoices/delivery_challan.blade.php`: printable delivery challan
- `app/Services/BillingService.php`: monthly service bill generation
- `app/Services/PaymentService.php`: payment recording and invoice due update
- `app/Http/Controllers/PaymentAccountController.php`: account balances and ledger

## Route And Permission Model

All business routes are inside `auth` middleware in `routes/web.php`.

Login routes are public only for guests:

- `GET /login`
- `POST /login`

Logout requires auth:

- `POST /logout`

Permission middleware alias is registered in `bootstrap/app.php`:

```php
'permission' => EnsureUserHasPermission::class
```

Current permission keys:

```text
view_dashboard
manage_customers
manage_packages
manage_invoices
finalize_invoices
manage_payments
manage_payment_accounts
manage_tickets
manage_products
manage_users
download_backup
```

When adding a new module:

1. Add a permission row in a new migration.
2. Add the route group under `auth`.
3. Apply `permission:your_permission_key`.
4. Add sidebar menu entry in `resources/views/layouts/app.blade.php`, guarded by `auth()->user()?->hasPermission(...)`.
5. Give the permission to the admin role in the same migration if existing admins should get it automatically.

## Access Control Schema

Tables:

- `roles`
- `permissions`
- `role_user`
- `permission_role`
- `permission_user`

Permission behavior:

- A user can get permissions from roles.
- A user can also get direct permissions.
- `User::hasPermission($permission)` checks direct permissions first, then role permissions.

User management screens:

- `/users`
- `/users/create`
- `/users/{user}/edit`

Role management screens:

- `/roles`
- `/roles/create`
- `/roles/{role}/edit`

Only users with `manage_users` can access these.

## Invoice Workflow

Invoices have a draft/final workflow.

Important columns:

- `finalized_at`: null means Draft, non-null means Final
- `status`: payment status such as `unpaid`, `partial`, `paid`
- `invoice_type`: currently `product` or `service`
- `vat`: invoice VAT amount

New product invoices:

- Created from `/invoices/create`
- Start as Draft because `finalized_at` is null
- Can be edited until finalized
- Can create/select customers from the invoice form
- Can add multiple line items
- Supports discount and VAT

Final behavior:

- `POST /invoices/{invoice}/finalize`
- Requires `finalize_invoices`
- Sets `finalized_at`
- Final invoices cannot be edited

Edit behavior:

- `GET /invoices/{invoice}/edit`
- `PUT /invoices/{invoice}`
- Blocked if `finalized_at` is set

Customer search in invoice form:

- Endpoint: `GET /invoice-customers/search?q=...`
- Searches by name, phone, or connection ID
- Used by JavaScript in `resources/views/invoices/create.blade.php`
- If no customer is selected, a new customer is created during invoice save
- If the typed phone already exists, the old customer is reused

## Monthly Bill Generation

Monthly bills are service invoices generated from active subscriptions.

Entry points:

- Dashboard Generate Bills form
- Invoices Generate Bills form
- Controller: `InvoiceController::generate`
- Service: `BillingService::generateMonthlyBills`

Rules:

- Only active subscriptions are considered.
- Customer must also be active.
- One service invoice per customer/month is created via `firstOrCreate`.
- Product invoices can have multiple invoices per customer/month.

If `0 invoice(s)` happens:

- Check `subscriptions` table.
- Check active customer status.
- Check active package assignment.
- Check whether service invoice already exists for that month.

## Printable Documents

Documents are based on existing invoice data.

Routes:

- Bill: `/invoices/{invoice}/challan`
- Quotation: `/invoices/{invoice}/quotation`
- Delivery challan: `/invoices/{invoice}/delivery-challan`

Views:

- `resources/views/invoices/challan.blade.php`
- `resources/views/invoices/quotation.blade.php`
- `resources/views/invoices/delivery_challan.blade.php`

Branding currently used:

```text
Ultimate Solution
your ultimate IT partner
44/1 K Khan Road, Kushtia
Mobile - 01812707070, 01798987928
us.com.bd | info@us.com.bd
```

Print behavior:

- A toolbar appears before printing.
- Toolbar is hidden in print via CSS.
- There is a checkbox: `Signature ছাড়াই print`
- If checked, signature lines are hidden and English text appears:
  - Bill: `Computer-generated bill / No signature required`
  - Quotation: `Computer-generated quotation / No signature required`
  - Delivery challan: `Computer-generated delivery challan / No signature required`

Amounts:

- Discount row is hidden if discount is 0.
- VAT row is hidden if VAT is 0.
- Bill and quotation show amount in words.

## Payment Flow

Payment entry:

- `/payments/create`
- Controller: `PaymentController`
- Service: `PaymentService`

Supported payment methods:

```text
cash
bkash
nagad
bank
```

Rules:

- Cash does not require a payment account.
- bKash, Nagad, and Bank require a payment account.
- The payment form can select an existing account.
- The payment form can create a new account inline.
- Payment amount cannot exceed invoice due amount.
- Recording a payment updates:
  - `paid_amount`
  - `due_amount`
  - `status`

Status update rules in `PaymentService`:

- Due becomes 0: `paid`
- Due remains after payment: `partial`

## Payment Accounts And Ledger

Payment accounts exist for bKash, Nagad, and Bank.

Tables:

- `payment_accounts`
- `payments.payment_account_id`

Important fields:

- `payment_method`
- `account_name`
- `account_number`
- `opening_balance`
- `status`

Screens:

- `/payment-accounts`: all payment methods, account details, balances
- `/payment-accounts/create`: add account
- `/payment-accounts/{payment_account}`: detailed ledger

Balance formula:

```text
Current Balance = Opening Balance + payments collected in this account
```

Cash balance is calculated from all cash payments:

```text
Cash Balance = SUM(payments.amount WHERE payment_method = cash)
```

Ledger page shows:

- Opening balance
- Total collection
- Current balance
- Transaction count
- Transaction rows with invoice, customer, note, credit, and running balance

## Database Backup

Route:

- `/backup/database`

Controller:

- `DatabaseBackupController::download`

Permission:

- `download_backup`

Behavior:

- Streams a `.sql` file download.
- Uses Laravel DB connection.
- Exports `DROP TABLE`, `CREATE TABLE`, and `INSERT` statements.
- Designed for MySQL.

Known limitation:

- The backup generator is simple and app-level. For very large databases, prefer `mysqldump`.

## Inventory

Files:

- `ProductController`
- `InventoryService`
- `Product`
- `StockMovement`

Routes:

- `/products`
- `/products/create`
- `POST /products/{product}/stock`

Permission:

- `manage_products`

Stock movement behavior:

- `in` increases stock.
- `out` decreases stock.
- Out movement fails if quantity exceeds stock.

## Support Tickets

Files:

- `TicketController`
- `SupportTicket`

Routes:

- `/tickets`
- `/tickets/create`
- `POST /tickets`

Permission:

- `manage_tickets`

Tickets can be assigned to users.

## Customers And Subscriptions

Files:

- `CustomerController`
- `Customer`
- `Subscription`
- `InternetPackage`

Routes:

- `/customers`
- `/customers/create`
- `/customers/{customer}`
- `/customers/{customer}/edit`

Permission:

- `manage_customers`

Important behavior:

- Creating a customer can optionally create an active subscription.
- Editing a customer can assign/change/remove active package.
- Removing the package marks the active subscription inactive.

Monthly bills need active subscriptions.

## Packages

Files:

- `PackageController`
- `InternetPackage`

Routes:

- `/packages`
- `/packages/create`

Permission:

- `manage_packages`

Packages are used by subscriptions and monthly service bills.

## Dashboard

File:

- `DashboardController`
- `resources/views/dashboard.blade.php`

Permission:

- `view_dashboard`

Dashboard shows summary stats and recent invoices/tickets.

It also shows:

- Generate Bills button if user has `manage_invoices`
- Download DB Backup button if user has `download_backup`

## Common Update Recipes

### Add A New Permission

1. Create a migration that inserts into `permissions`.
2. Optionally attach it to admin role in `permission_role`.
3. Wrap routes with `permission:new_key`.
4. Hide/show UI via `auth()->user()?->hasPermission('new_key')`.
5. Run:

```bash
php artisan migrate
php artisan route:list
php artisan test
```

### Add A New Printable Document

1. Add a method to `InvoiceController`.
2. Load `customer` and `items`.
3. Add a route inside `permission:manage_invoices`.
4. Add a Blade view under `resources/views/invoices`.
5. Add a button in `resources/views/invoices/show.blade.php`.
6. Keep print CSS standalone; do not extend the admin layout.

### Add A Field To Invoice

1. Add a migration for `invoices`.
2. Add the field to `Invoice::$fillable`.
3. Add validation in `InvoiceController::validateInvoiceData`.
4. Add calculation/update logic in `prepareInvoiceData`, `store`, and/or `update`.
5. Update create/edit Blade form.
6. Update print views if needed.

### Add A Payment Method

1. Update validation in `PaymentController`.
2. Update `PaymentAccountController` validation if account-based.
3. Update payment form JavaScript in `resources/views/payments/create.blade.php`.
4. Update labels in payment account views.
5. Update any balance logic that assumes method list.

## Testing Notes

Current tests are intentionally light:

- Guest users redirect to login.
- Login page returns 200.
- Unit example passes.

Run after every change:

```bash
php artisan test
php -l routes/web.php
```

For controller/view changes, also run `php -l` on touched PHP/Blade files.

## Safety Notes For Future AI Agents

- Do not remove auth middleware unless the user explicitly asks.
- Do not expose backup download without `download_backup`.
- Do not allow finalized invoices to be edited.
- Do not create duplicate customers when phone already exists.
- Do not require payment account for cash.
- Do require payment account for bKash/Nagad/Bank.
- Keep printable documents standalone; they should not include the sidebar layout.
- Existing database may contain user data. Avoid `migrate:fresh` unless the user explicitly approves data loss.
- When adding migrations, prefer forward-compatible changes with defaults.
