# AI Maintainer Guide

This guide is for another AI agent or developer who needs to update this Laravel project safely.

## Working Style

- Work like a highly skilled Laravel, PHP, and OLT engineer.
- Keep token and cost use low: inspect only the files needed, make focused changes, avoid repeated broad searches, and prefer concise verification.
- Keep every production or code update traceable in Git: review `git status`,
  make focused commits with detailed messages, and include what changed, why it
  changed, and how it was verified in the commit message or project notes.

## Expert Operating Roles

When changing this project, act as a small senior team rather than a single
generic coder. Before touching code, identify which roles below are relevant and
let those responsibilities shape the implementation, validation, and notes.

### 1. Laravel Application Architect

- Preserve the existing Laravel patterns: controllers, Eloquent models, Blade
  views, services, migrations, policies/permissions, and PHPUnit tests.
- Keep business logic out of Blade where possible. Use services or controller
  helpers when the same rule must protect both browser forms and direct POSTs.
- Prefer small, reversible migrations and data-safe defaults. Do not assume the
  database is empty.
- Keep routes, validation, model fillable fields, casts, relationships, and
  tests in sync.

### 2. ISP Operations Specialist

- Understand that this app runs a real ISP/computer-service business. Customer
  status, subscriptions, MikroTik sync, OLT ONU registration, bills, payments,
  support tickets, stock, and warranty records are operationally connected.
- Protect live network workflows. OLT/MikroTik changes must be conservative,
  observable, and documented with command assumptions.
- Keep customer-facing identifiers clear: connection ID, MikroTik username,
  phone number, ONU serial, product serial, invoice number, and ticket number
  must not be confused.

### 3. Inventory And Serial Lifecycle Expert

- Treat inventory as a lifecycle, not only a quantity counter: purchase,
  in-stock, own-use, sold, returned, repaired, replaced, scrapped, and vendor
  warranty states may all matter.
- Serial-tracked products must have strict availability checks and audit trails.
  Ranges such as `1001-1004` and Bengali digit ranges such as `১০০১-১০০৪`
  should be parsed consistently wherever serials are accepted.
- Quantity should follow serial count when serials are provided, and the backend
  must enforce the same rule as the UI.
- Never show or require serial input for products that do not track serials.

### 4. Service Product And Warranty Architect

- Model service products separately from stock products. A service item can be
  invoiced but should not create stock movements or require serial numbers.
- Warranty behavior should be attached to sold products/serials and optionally
  to service products as a service guarantee period.
- Design warranty flows around real shop operations: customer claim, receive
  item, diagnose, repair, replace, return to vendor, reject, charge paid
  service, deliver, and close.
- Replacement must update both old and new serial states and preserve a clear
  history of what happened.
- Vendor warranty/return tracking should connect faulty serials back to the
  purchase vendor whenever possible.

### 5. Billing, Ledger, And Accounting Guardian

- Every money movement must be traceable: invoice, payment account, allocation,
  customer balance transaction, cash ledger, expense, or salary payment.
- Do not update customer balances directly for payment flows. Use the existing
  payment/allocation services and maintain ledger integrity.
- Invoice totals, discounts, VAT, due amount, paid amount, stock movement, and
  serial movement must stay consistent inside transactions.
- Payment account and cash ledger running balances must include both payment
  collection credits, direct advance receipts, and expense debits. A direct
  advance is a `customer_balance_transactions` credit with `payment_id = null`.
  Never add a balance credit whose `payment_id` is set because its parent
  `payments` row already represents the received money.
- Product/service invoices and monthly ISP service bills have different
  business meanings; keep their rules separate.
- Standalone quotations live in `quotations` and `quotation_items`. They may
  calculate a quoted total for the document, but must not affect invoice
  totals, party dues, payments, ledgers, stock, or serial status.
- Quotation save/update should still validate serial-tracked lines the same
  way invoice and purchase flows do: `serial count + serial-less quantity`
  must equal line quantity. This prevents operators from saving a quotation
  that later fails during invoice conversion for a predictable counting issue.
- `POST /quotations/{quotation}/make-invoice` creates one draft invoice from
  the quotation inside a transaction. Inventory and serial availability are
  checked and applied only at conversion time; repeated conversion returns the
  existing invoice instead of creating a duplicate.
- `POST /invoices/{invoice}/copy-next-month` is a recurring/manual draft-copy
  helper. It must not duplicate stock-bound `product_id`, serial numbers, or
  serial-less counts, because copying a stock sale is not the same as issuing
  stock again. Keep copied stock lines as manual text/price lines unless a
  future workflow explicitly re-selects available stock and applies inventory.

### 6. UX Designer For Office Operators

- Design forms for fast counter/service-desk entry: keyboard-friendly,
  searchable, minimal unnecessary fields, and immediate feedback.
- Show fields only when they are useful. For example, hide serial controls for
  non-serial products and hide stock warnings for pure service products.
- Keep printable invoices, challans, quotations, and reports clean and
  standalone.
- When adding complex workflows such as warranty claims, provide list filters,
  status badges, clear action buttons, and customer/product history links.

Network-map linking rules:

- Treat Router, Switch, OLT, Splitter, TJ Box, and ONU as linkable equipment
  with explicit endpoints/ports.
- Every direct equipment link must be reciprocal: both endpoint properties must
  contain the peer feature, peer port, medium, and color. Unlinking or editing
  either side must update both sides.
- Every equipment port must support both drag-to-target linking and a searchable,
  naturally sorted target list. Keep fiber-core drag/drop as an additional path.
- Direct link media are `Fiber` or `Copper`; operators may override the display
  color. Multiple links on the same geometry must use stable parallel offsets.
- Keep compatibility with legacy `direct_router` and one-sided splitter rows;
  normalize them to reciprocal `direct_device` links when topology loads.

### 7. QA And Regression Engineer

- Add focused tests whenever business rules change, especially for inventory,
  serials, invoice totals, payments, customer status, OLT parsers, and
  warranty/service workflows.
- Run targeted tests first, then broader tests when shared services or parser
  behavior changes.
- For controller/view changes, run `php -l` on touched PHP files and compile
  Blade views with `php artisan view:cache`; clear compiled views afterwards
  with `php artisan view:clear`.

### 8. Security And Production Reliability Engineer

- Keep authentication, permissions, CSRF protection, and production secrets
  safe. Never store real credentials in the repository.
- Avoid destructive production operations. Never use `migrate:fresh` or data
  loss commands unless the operator explicitly approves.
- Production changes should be deployable with backup, rollback, and
  verification steps.
- Treat backup download, SMS webhooks, router/OLT credentials, and payment data
  as sensitive surfaces.

### 9. Documentation Maintainer

- Update this guide whenever business rules, operational assumptions, parser
  behavior, stock/warranty flows, OLT command behavior, payment rules, or
  deployment steps change.
- Record not only what the code does, but why the business needs that behavior.
- Keep the documentation practical for the next maintainer: file names, routes,
  permissions, commands, and test names are more useful than vague summaries.

### Default Decision Order

When roles disagree, use this priority:

1. Protect data integrity and customer/network operations.
2. Preserve accounting and inventory correctness.
3. Keep the operator workflow fast and understandable.
4. Match existing Laravel patterns.
5. Add tests and documentation for every important business rule.

## Project Snapshot

This is a Laravel 12 app for an ISP/computer-service business named **Ultimate Solution**.

Main capabilities:

- Login-protected admin system
- User, role, and permission management
- Customers and internet packages
- OLT ONU live polling and optical power inventory
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

## Production Deployment

Live production site:

```text
https://finalaccess.com
```

Production server/app details:

```text
SSH host: 162.4.6.7
SSH user: anike
Laravel root: /home/finalaccess.com/public_html
Runtime user: final4810
Git branch: main
```

Do not store real SSH passwords, `.env` secrets, database passwords, or SMS
tokens in this repository.

For complete production update steps, backup commands, rollback commands, cron
paths, webhook URL, ownership notes, and troubleshooting, read:

```text
DEPLOYMENT.md
```

Minimum deploy flow:

```bash
php artisan test
git push origin main
ssh anike@162.4.6.7
sudo -u final4810 bash
cd /home/finalaccess.com/public_html
git pull --ff-only origin main
php artisan optimize:clear
```

If migrations are included:

```bash
php artisan migrate --force
php artisan optimize:clear
```

Always check server-local changes before pulling:

```bash
cd /home/finalaccess.com/public_html
git status -sb
```

## Important Files

- `DEPLOYMENT.md`: finalaccess.com production deployment runbook
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
- `app/Http/Controllers/PurchaseBillController.php`: vendor purchase bills, stock entry, serial/warranty capture
- `resources/views/invoices/create.blade.php`: create and edit invoice form
- `resources/views/invoices/show.blade.php`: invoice details and final button
- `resources/views/invoices/challan.blade.php`: printable bill
- `resources/views/invoices/quotation.blade.php`: printable quotation
- `resources/views/invoices/delivery_challan.blade.php`: printable delivery challan
- `app/Services/BillingService.php`: monthly service bill generation
- `app/Services/PaymentService.php`: payment recording and invoice due update
- `app/Services/InventoryService.php`: stock in/out/own-use movement and stock balance updates
- `app/Http/Controllers/PackageController.php`: internet package create/edit/show workflow
- `app/Http/Controllers/PaymentAccountController.php`: account balances and ledger
- `app/Http/Controllers/OltOnuController.php`: OLT device setup, live refresh, and ONU inventory
- `app/Services/OltSnmpClient.php`: optional SNMP-first single ONU status/power polling for fast row refresh
- `app/Services/OltTelnetClient.php`: Telnet client for live OLT polling
- `app/Services/OltLiveOutputParser.php`: parses live OLT output into ONU records
- `resources/views/olt_onus/*`: OLT setup, live refresh, and list pages

## Documentation Maintenance

Every AI agent or developer must update the Markdown docs whenever a change affects behavior, setup, deployment, or operations. Do this in the same task before saying the work is done.

Update these files as needed:

- `README.md`: user-facing feature list, routes, setup, commands, and basic operations
- `AI_MAINTAINER_GUIDE.md`: architecture, important files, business rules, route/permission model, implementation notes, limitations, and testing guidance
- `DEPLOYMENT.md`: finalaccess.com deployment steps, server paths, migrations, cache clearing, cron, rollback, and troubleshooting
- `PROJECT_ROADMAP.md`: future plans, larger module direction, or roadmap-level decisions

Documentation must be updated when changing:

- routes, controllers, views, menus, permissions, or public URLs
- database migrations, models, relationships, or required artisan commands
- billing, payment allocation, bKash SMS, customer status, grace period, MikroTik sync, OLT live polling, or accounting rules
- audit/version-history behavior, including what old snapshots contain and how operators view previous versions
- production deploy flow, server path, ownership, cache commands, migrations, cron, scheduler, webhook URLs, or backup/rollback process
- external integrations, `.env` keys, device connection methods, command names, ports, or known limitations
- tests, troubleshooting steps, or operational recovery instructions

Never commit real secrets to Markdown. Use placeholders and tell maintainers to get passwords/tokens from the approved secure source.

### Record Version / Edit History Notes

- Edited records are preserved in `record_versions` with `old_values`, `new_values`, `changed_fields`, `edited_by`, `edited_by_type`, and `edited_by_name`.
- Invoice and quotation edits use `RecordVersionService` to snapshot the document, party, and line items before and after the edit. This is intentional because document line items are deleted/recreated during draft edits.
- Invoice finalization, including bulk finalization, must also create full invoice snapshots. Avoid query-builder `update(...)` for operator-facing invoice state changes unless you manually write a `record_versions` row.
- Party edits update party fields, package/subscription changes, and the version row inside one DB transaction so history cannot drift from the actual party state.
- Full-document and party snapshots must be taken after locking the edited row inside the transaction. Finalization must re-check the locked invoice so concurrent requests cannot create duplicate versions.
- Snapshot normalization removes technical IDs, timestamps, entry metadata, and pivots so deleting/recreating unchanged line items does not create false edits. Sensitive nested fields remain masked.
- Role permission and user role/permission pivot changes must be recorded explicitly; the generic `updated` observer cannot see `sync(...)` changes.
- Simple operator-editable model edits use `RecordVersionObserver` for attribute-level old/new history. Sensitive fields containing password, token, secret, or key are masked.
- Do not attach the generic observer to high-churn generated records such as live OLT polling rows, payment allocations, customer balance transactions, stock movements, or SMS status rows unless you also suppress system/background updates. Otherwise normal refresh/accounting work can create excessive history noise.
- Product stock-only updates are represented by stock movements and are excluded from generic record versions. Initial purchase-bill subtotal calculation is also suppressed because it is creation, not an operator edit.
- Operator pages should not show raw JSON as the primary history view. `resources/views/partials/record_versions.blade.php` renders invoice old versions in a full-width invoice-like preview with a distinct history background. Do not add fake action labels such as `History Copy`, and do not place the old-version preview inside a narrow table column; it must use the full content width so the historical invoice/record is readable. Keep future history UI readable first, with raw data only as a secondary/debug option if needed.
- Detail pages paginate edit history with the `history_page` query parameter and order by descending record-version ID so same-second edits remain deterministic.

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
- `discount_type`/`discount_value` and `vat_type`/`vat_value`: original user inputs used to recalculate percentage adjustments when draft invoice items change
- `payment_note`: optional invoice-specific bill payment note override; blank means use the global default from `app_settings.invoice_payment_note`
- `public_note`/`show_public_note`: optional customer-facing note and the flag that allows it to appear on printed documents
- `private_note`: internal office note; never render it on customer-facing printed documents

New product invoices:

- Created from `/invoices/create`
- Start as Draft because `finalized_at` is null
- Can be edited until finalized
- Can create/select customers from the invoice form
- Can add multiple line items
- Supports discount and VAT
- The create form accepts `?type=product` or `?type=service`; service type pre-fills a simple service-charge line while still allowing editable line items.
- Quick item chips in `resources/views/invoices/create.blade.php` help counter operators add common rows such as Router, Installation charge, Service charge, and Cable without using a rigid dropdown.

Invoice list operator tools:

- `/invoices` shows summary cards for filtered invoice count, total due, total billed, draft/final counts, paid/unpaid/partial counts, total customer advance balance, and the current month's service-bill generation preview count.
- Filters include search, billing month, payment status, invoice type, final state, minimum due, due date range, and a due-only checkbox.
- Row actions include View, Edit Draft, Payment shortcut, Print Bill, Quotation, Delivery Challan, and Copy Next Month.
- Row colors intentionally distinguish paid, due, and overdue invoices so operators can scan the list quickly.
- Customer account balance is visible on the invoice list to help decide whether advance balance can be applied.

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
- Bill payment note uses `invoices.payment_note` when present; otherwise it uses the global `invoice_payment_note` setting. The default can be edited from Billing -> Payment Note Default.
- Invoice public notes appear only when `show_public_note` is true. Private notes stay on the admin invoice page only and must not be rendered in bill, quotation, or delivery challan views.

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

Current rules:

- Cash does not require a payment account.
- bKash, Nagad, and Bank require a payment account.
- The payment form can select an existing account.
- The payment form can create a new account inline.
- Payment amount may be greater than the selected invoice due.
- Oldest due invoices must be cleared first.
- Any extra amount after all due invoices are cleared stays in customer advance balance.
- Recording a payment updates:
  - `paid_amount`
  - `due_amount`
  - `status`
  - `payment_allocations`
  - `customer_balance_transactions` when advance is added or consumed

Status update rules in `PaymentService`:

- Due becomes 0: `paid`
- Due remains after payment: `partial`
- Customer line/subscription is activated only when total remaining due across all invoices is zero.

## Payment Accounts And Ledger

Payment accounts exist for bKash, Nagad, and Bank.

Tables:

- `payment_accounts`
- `payments.payment_account_id`
- `payment_allocations`
- `customer_balance_transactions`

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
                + direct advance receipts - expenses paid from this account
```

Cash balance is calculated from all cash payments:

```text
Cash Balance = cash payments + direct cash advance receipts - cash expenses
```

Ledger page shows:

- Opening balance
- Total collection
- Current balance
- Transaction count
- Transaction rows with invoice, customer, note, credit, and running balance
- Direct advance rows from `customer_balance_transactions` where `direction = credit` and `payment_id IS NULL`
- Expense debit rows
- Payment allocation summary, so one payment can be audited across multiple invoices.
- Advance balance credits and advance-used memo rows.

Important accounting rule:

- `payments` is the receipt row.
- `customer_balance_transactions` rows with `payment_id` set are allocation/balance detail for an existing receipt and must not be counted again in payment-account totals.
- Applying advance to an invoice is an internal balance allocation, not cash leaving the business, so advance-use debits do not reduce a payment account or cash ledger.
- `payment_allocations` records exactly which invoice received how much.
- `customer_balance_transactions` records advance balance increase/decrease.
- Do not update `customers.account_balance` directly for payment/bKash flows; use `PaymentService`.

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
- `PurchaseBillController`
- `InventoryService`
- `Product`
- `PurchaseBill`
- `PurchaseBillItem`
- `ProductSerial`
- `StockMovement`
- `Warehouse`
- `ProductWarehouseStock`
- `WarehouseController`

Routes:

- `/products`
- `/products/create`
- `POST /products/{product}/stock`
- `/warehouses`
- `/warehouses/{warehouse}`
- `/warehouse-transfers/create`
- `POST /warehouse-transfers`
- `/warehouse-movements`
- `/purchase-bills`
- `/purchase-bills/create`
- `/purchase-bills/{purchase_bill}`

Permission:

- `manage_products`

Stock movement behavior:

- Products have optional `brand` plus a fixed cascading `product_categories` tree.
- `products.product_category_id` stores the selected leaf/category. The legacy `category` and `subcategory` string fields are kept in sync for display/search compatibility.
- Product category trees can have unlimited levels through `product_categories.parent_id`.
- Product forms and purchase bill product selection show child category lists automatically after a parent category is selected.
- Product lists and purchase bill product selection can filter by brand and category tree.
- Products may behave as stock goods, serial-tracked goods, consumables, service
  items, or warranty/service items. Keep the behavior explicit in both backend
  rules and form UI.
- `in` increases stock.
- `out` decreases stock.
- `use` decreases stock for items used inside the business.
- Operators use `Inventory > In-house Use` (`/in-house-use`) as an invoice-style create-only screen: employee/date/purpose are the shared header and one submission can atomically issue multiple product/serial rows. The shared header also accepts one optional approval scan/PDF (PDF/JPG/PNG/WEBP, max 10 MB), whose private-storage metadata is copied to every assignment created by that submission.
- In-house product rows use the invoice lookup pattern: writable name/SKU/barcode/brand search with a hidden required `product_id`, quantity, serial/serial-less controls, editable `unit_price`, calculated line `total`, and a calculated total asset value. Unit value defaults to product purchase price because this is internal asset cost, not a customer sale.
- Reports are intentionally separate pages: `/in-house-use/reports/employees` for employee issued/returned/holding details, `/in-house-use/reports/used-stock` for reusable returned stock, and `/in-house-use/reports/history` for assignment lifecycle history.
- Employee and history reports calculate issued, returned, and outstanding values from the assignment's saved unit price, so later product-price changes do not rewrite old employee asset values.
- New-stock assignment creates the normal `use` stock movement and marks selected serials `used`. Generic product stock forms do not expose internal use; they link to the employee assignment workflow.
- Partial or full employee returns are stored in `employee_asset_returns`. Returned quantity goes to `used_product_warehouse_stocks`, and returned serials become `used_in_stock`.
- Returned used stock is deliberately separate from `products.stock_quantity` and `product_warehouse_stocks`, so invoice/new-stock flows cannot accidentally sell it as new. It can be selected as `Returned Used Stock` and reissued through the same employee workflow.
- Core write/detail routes are `GET/POST /in-house-use`, `GET /in-house-use/{assignment}`, `GET /in-house-use/{assignment}/approval-document`, and `POST /in-house-use/{assignment}/returns`; all create, document, and report routes use `manage_products`.
- Approval files are stored on the private `local` disk under `in-house-approvals/YYYY/MM`; never expose those paths with a public symlink. Serve them only through the permission-protected controller route.
- Asset return dates must be on or after the assignment date. Returned quantities go only to `used_product_warehouse_stocks`; they must never increase new/saleable stock.
- Every stock movement belongs to a warehouse and records the warehouse balance before and after the movement.
- Movement history preserves serial-number snapshots, reference, reason, related warehouse, and entry operator for audit use.
- Out and own-use movements fail if quantity exceeds the selected warehouse stock, even when total product stock is higher.
- `products.stock_quantity` remains the aggregate across all warehouses; `product_warehouse_stocks` is the warehouse-level source of truth.
- Transfers create paired `transfer_out` and `transfer_in` movements with one reference number and do not change aggregate product stock.
- The transfer form accepts draggable multi-product rows under one From/To warehouse selection, supports adding a chosen number of rows, and keeps product/serial state when rows are reordered; all rows share one reference and roll back together if any row fails.
- In-stock serials carry `warehouse_id`; serial transfers must update both the warehouse balances and each selected serial location atomically.
- Existing stock and in-stock serials are assigned to the seeded `Main Warehouse` by the warehouse migration.
- Purchase bills and invoices use the default warehouse unless a future workflow explicitly captures another warehouse.
- Purchase bills add stock and create a stock movement using the purchase bill number as the reference.
- Purchase bills accept one optional vendor bill/invoice copy (PDF/JPG/PNG/WEBP, max 10 MB), stored on the private `local` disk under `purchase-bill-documents/YYYY/MM` and served through `GET /purchase-bills/{purchaseBill}/document` behind `manage_products`.
- Draft purchase-bill edits preserve the existing document when no file is submitted. A successful replacement deletes the old file; a failed database operation deletes the newly uploaded file so private storage does not accumulate orphan replacements.
- Sale returns must apply credit to the locked source invoice before touching customer advance: `invoice_credit_amount = min(return subtotal, current due)` and only `advance_credit_amount = subtotal - invoice credit` enters `customer.account_balance`. A fully settled invoice with no payment is `returned`; a partly settled invoice is `partial`. Never credit the full return to advance while leaving the same invoice due, because customer pages then show a misleading unpaid invoice offset by hidden advance.

### Vehicle and fleet management

- `manage_fleet` protects the Fleet menu and every `/fleet` route. The module schema and operator workflow are documented in `FLEET_MANAGEMENT.md`.
- `vehicle_assignments_history` is the only source of truth for current and former Driver/Helper/Supervisor duty. Current means `end_date IS NULL`; never replace a row to change staff. Use `FleetService::assignEmployee()` so the previous vehicle/role occupant and the employee's other active duty (regardless of role) are locked and closed transactionally.
- Maintenance due state is true when either `next_due_date <= today` or current vehicle mileage reaches `next_due_mileage`. `FleetService::logMaintenance()` recalculates both schedules from the saved service event.
- Fleet expenses are separate from payroll/general `expenses`: they require a vehicle, preserve the creator user, may identify the responsible driver/employee, and support daily/trip metadata.
- Fleet report date ranges are inclusive. Duty filters use interval overlap, while expenses and maintenance use their actual event dates. Totals are SQL aggregates and detail tables paginate independently.
- Keep fleet reports as separate operator pages: `/fleet/reports` is only the selection hub; expenses, maintenance, and staff duty history live at `/fleet/reports/expenses`, `/fleet/reports/maintenance`, and `/fleet/reports/duty-history` with their own relevant filters and pagination.
- Fleet periodic-maintenance entry is centralized at `/fleet/maintenance/schedules`, repair/service logging at `/fleet/maintenance/logs/create`, and the read-only date/mileage due report at `/fleet/reports/maintenance-due`. Status is overdue if either date or mileage has passed, due if either equals the current date/mileage, upcoming otherwise, and unscheduled when both next-due fields are empty.
- Fleet maintenance and vehicle expense records stay editable only while `finalized_at` is null. Update/finalize actions must lock the row, store full snapshots through `RecordVersionService`, and show those versions on the detail page; once `Final & Lock` is used, never allow another edit.
- `vehicle_maintenance_logs.maintenance_item_id` is optional. Use `work_name` for one-off repairs; only logs linked to a periodic item recalculate that schedule.

### Sale returns and invoice settlement

- `sale_returns.subtotal` is the gross value of returned item rows. `credit_total` is the actual proportional invoice credit after invoice-level discount and VAT; `invoice_credit_amount + advance_credit_amount` must equal `credit_total`.
- Invoice due is always `max(0, total - paid_amount - SUM(sale_returns.credit_total))`. Use `Invoice::recalculateSettlement()` after cash or advance allocation; do not restore returned credit by calculating only `total - paid_amount`.
- An invoice with sale-return rows cannot be edited because its item IDs, restored stock, and credit history are already linked.
- Purchase bill item serial numbers are stored in `product_serials`; warranty end date is calculated from purchase date plus warranty months.
- Vendor/wholesale shops are stored in the existing `customers` table as parties with `is_vendor=true`.

### Service Products And Warranty Blueprint

This project must support two related but different ideas:

- **Service products**: invoiceable work with no physical stock, such as
  installation, fiber splicing, router configuration, line shifting,
  reconnection, emergency support, or maintenance visits.
- **Warranty/service lifecycle**: post-sale support for hardware or services,
  including claim, diagnosis, repair, replacement, vendor return, paid service,
  delivery, and closure.

Service product behavior:

- Service products are invoiceable but should not reduce stock.
- Service products should not show serial inputs unless they explicitly track
  serials, which should be rare.
- Service products may have a default sale price, VAT behavior, and optional
  service guarantee days.
- Service guarantee is not the same as hardware warranty. Hardware warranty is
  usually attached to a sold product serial; service guarantee is attached to an
  invoice service line.
- Service products should appear in invoice product search alongside stock
  products, but their UI should be simpler: product name, quantity, unit price,
  note, optional technician/schedule fields, and no serial/stock controls.
- Examples:
  - `Installation Charge`
  - `Fiber Splicing`
  - `Router Configuration`
  - `Line Shifting Charge`
  - `Maintenance Visit`
  - `Emergency Support`
  - `Reconnection Fee`
  - `Paid Warranty Service`

Recommended product type model:

```text
stock        Physical stock item, stock movement applies.
serial_stock Physical stock item with serial-level tracking.
consumable   Physical stock item, no serial, may be used internally.
service      Invoiceable service, no stock movement.
warranty     Service line used for warranty/repair charge or adjustment.
```

If adding a `product_type` field later, keep compatibility with the existing
`track_inventory` and `track_serial_numbers` booleans:

```text
stock/serial_stock/consumable -> track_inventory=true
service/warranty              -> track_inventory=false
serial_stock                  -> track_serial_numbers=true
service/warranty              -> track_serial_numbers=false by default
```

Invoice behavior for service products:

- Selecting a service product should hide serial controls and stock warnings.
- Service products should not call `InventoryService::moveStock`.
- Service lines should still affect invoice subtotal, discount, VAT, total,
  due, paid, and ledger flows normally.
- A service line can optionally create or link a support ticket when the service
  needs field work.
- A service line can optionally record:
  - `service_date`
  - `scheduled_at`
  - `assigned_to`
  - `service_location`
  - `service_note`
  - `service_guarantee_days`
  - `service_guarantee_until`

Serial and warranty behavior:

- Serial input supports one per line, comma groups, and ranges such as
  `1001-1004`, `ONU001-ONU003`, and Bengali digit ranges such as `১০০১-১০০৪`.
- When serials are provided, quantity should follow the expanded serial count.
  This must be enforced server-side, not only by JavaScript.
- Serial-tracked sales should mark matching `product_serials` as sold and link
  the action to the invoice number in the note/history.
- Serial-tracked stock entry should create `product_serials` with warranty end
  dates derived from purchase date plus warranty days/months.
- A sold serial should be treated as a customer asset. It should be visible from
  customer details, product details, invoice details, and future warranty claim
  screens.

Recommended customer asset display:

```text
Product | Serial | Invoice | Sold Date | Warranty Until | Status | Actions
ONU     | 1001   | INV-... | 2026-06-04 | 2027-06-04    | In warranty | Claim
```

Customer asset rules:

- If `warranty_until` is today or later, show `In warranty`.
- If `warranty_until` is before today, show `Expired`.
- If the product has no warranty date, show `No warranty`.
- If a claim is open, show the current claim status instead of only warranty
  status.
- Customer asset action buttons should include `Warranty Claim`, `Repair
  Entry`, `Replace`, and `View History` when those modules exist.

Warranty claim status lifecycle:

```text
pending          Claim recorded, item not yet received or diagnosis pending.
received         Customer item received by shop.
diagnosing       Technician is checking the issue.
repairing        Repair work is in progress.
sent_to_vendor   Faulty item sent to supplier/vendor.
vendor_returned  Vendor repaired/replaced/rejected and returned item.
ready            Item is ready for customer delivery.
delivered        Item delivered back to customer.
replaced         Replacement serial/product given to customer.
rejected         Claim rejected, with reason.
paid_service     Treated as paid repair/service outside warranty.
closed           Claim completed and locked for normal editing.
```

Warranty claim action types:

```text
repair          Repair same item.
replace         Give another serial/product as replacement.
vendor_return   Send faulty product to vendor/supplier.
reject          Reject claim, usually due to expired warranty or damage.
paid_service    Convert to chargeable repair/service.
return_only      Return same item without repair/replacement.
```

Recommended `warranty_claims` fields:

- `claim_no`
- `customer_id`
- `invoice_id`
- `invoice_item_id`
- `product_id`
- `product_serial_id`
- `claim_date`
- `received_at`
- `closed_at`
- `warranty_status`: `in_warranty`, `expired`, `no_warranty`, `unknown`
- `problem_description`
- `diagnosis_note`
- `action_type`
- `status`
- `assigned_to`
- `vendor_id`
- `replacement_product_id`
- `replacement_product_serial_id`
- `service_invoice_id`
- `service_charge`
- `resolution_note`
- `delivery_note`
- `entry_by`
- `entry_by_type`

Recommended `warranty_claim_logs` fields:

- `warranty_claim_id`
- `old_status`
- `new_status`
- `note`
- `entry_by`
- `entry_by_type`
- `created_at`

Claim creation rules:

- Prefer creating a claim from a sold serial/customer asset, not from free text.
- If no serial exists, allow manual product/service claim only with clear notes.
- Auto-detect warranty status from `product_serials.warranty_until`.
- Expired warranty should not block claim creation; it should change available
  actions toward `paid_service`, `reject`, or manual approval.
- Do not allow claim creation for serials still `in_stock` unless the claim is a
  vendor/internal stock warranty case.
- If there is already an open claim for the same serial, show it and prevent
  duplicate open claims unless explicitly allowed.

Replacement rules:

- Replacement must update both the old and new serial states inside one
  transaction.
- Old serial recommended statuses: `replaced`, `returned_to_vendor`,
  `repairing`, or `scrapped`, depending on the action.
- New replacement serial must be available before assignment.
- New replacement serial should become assigned/sold to the same customer.
- Warranty policy must be explicit:
  - Default recommendation: replacement keeps original warranty end date.
  - Optional policy: replacement gets new warranty from replacement date.
  - Optional policy: replacement gets vendor-provided remaining warranty.
- The claim log must record old serial, new serial, operator, date, and policy.

Vendor warranty/return rules:

- Vendors are stored in `customers` with `is_vendor=true`.
- A vendor return should connect the faulty serial to the original purchase
  vendor when possible.
- Track sent date, courier/reference, vendor receive date, vendor decision,
  returned/replacement serial, and vendor notes.
- Vendor statuses should be reportable separately from customer claim statuses,
  because a customer may receive a replacement before the vendor case is fully
  closed.

Paid repair/service conversion:

- Out-of-warranty repair can create a normal product/service invoice.
- The invoice should link back to the warranty claim through `service_invoice_id`
  or a similar field.
- Paid repair should never silently change customer balance; it must follow the
  normal invoice/payment flow.
- If parts are used for repair, those parts should create stock `out` or `use`
  movements with a reference to the warranty claim.

Support ticket integration:

- Warranty claim may create or link a support ticket when field visit or
  technician work is required.
- Ticket assignment and claim assignment can be the same employee, but they
  should remain separate concepts.
- Closing a ticket should not automatically close a warranty claim unless the
  user explicitly chooses that action.

Recommended screens:

- `/warranty-claims`: searchable list by claim no, customer, phone, product,
  serial, vendor, status, and date.
- `/warranty-claims/create`: create from customer asset or manual entry.
- `/warranty-claims/{claim}`: claim timeline, customer, product, serial,
  warranty status, actions, notes, files/photos later if needed.
- Customer details: `Assets & Warranty` section.
- Product details: serial list with status and claim links.
- Vendor details: pending vendor warranty items.
- Dashboard widgets: open claims, ready for delivery, vendor pending,
  expired/in-warranty claim counts.

Recommended permissions:

```text
manage_warranty_claims
view_warranty_claims
close_warranty_claims
manage_service_products
```

Reports to add later:

- Active warranty claims by status.
- Ready for delivery list.
- Vendor pending warranty items.
- Expired warranty claims.
- Product/brand fault rate.
- Replacement count by product/brand/vendor.
- Technician repair workload.
- Paid repair revenue.
- Parts used in repair.

Testing requirements for service and warranty work:

- Service product invoice does not create stock movement.
- Service product invoice still affects invoice totals and payment flow.
- Non-serial products hide/ignore serial input in UI and reject serials
  server-side when submitted manually.
- Serial range quantity expansion works in purchase bills, invoices, and stock
  movements.
- Warranty claim detects in-warranty, expired, and no-warranty correctly.
- Duplicate open claim for same serial is blocked or clearly controlled.
- Replacement updates old serial, new serial, customer asset display, and claim
  log inside one transaction.
- Paid repair creates/links invoice without bypassing payment allocation rules.

Recommended implementation order:

1. Add explicit product type/service behavior while preserving existing booleans.
2. Harden invoice UI and backend for service products.
3. Add customer asset display from sold serials.
4. Add warranty claim tables, model, routes, permissions, and list/detail pages.
5. Add claim create/receive/diagnose/close actions with logs.
6. Add replacement flow.
7. Add vendor warranty return tracking.
8. Add paid repair invoice linking.
9. Add reports and dashboard widgets.

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
- When changing OLT/ONU behavior, OLT command sequences, parser rules, firmware-specific workarounds, migrations, troubleshooting findings, or operational decisions, update this guide in the same change. Do not wait for the operator to ask for documentation separately.

## Latest Business Rules

Read this section before changing billing, bKash SMS, customer status, or MikroTik code.

### Customer Identity And Balance

- Customer `connection_id` is optional for product-only customers who are not ISP subscribers.
- When assigning an internet package/subscription, `connection_id` is required because it becomes the ISP/MikroTik user ID.
- Customer `mikrotik_username` is displayed as User ID on `/customers`; if missing, use `connection_id`; if both are missing, treat the customer as product-only.
- The `customers` table also acts as the party ledger. Use `is_customer` and `is_vendor` to classify parties as customer, vendor, or both.
- A party must have at least one role selected. Vendor-only parties can be used for wholesale purchase bill entry.
- Default customer MikroTik/PPPoE password is `4321`.
- `customers.account_balance` stores advance/extra money only.
- Customer net balance shown in lists is:

```text
account_balance - total_due_amount
```

- Negative net balance means the customer owes money.

### Billing Generation

- The `/invoices` page `Generate Bills` action only creates monthly service bills for customers marked `never_suspend`.
- `never_suspend` means this is a special customer whose line should never be closed and whose monthly bills are generated by the bulk button.
- Normal customers do not get monthly bills from the bulk generate button.
- For normal customers, current-month service bill is generated when bKash payment SMS is received and matched to that customer.
- Previous due matters. Do not activate a customer line just because the current month bill is paid. Activate only when total remaining due across all invoices is zero.

### Payment Allocation

- `PaymentService::recordPayment()` accepts payments greater than the selected invoice due.
- Allocation uses:

```text
customer.account_balance + new payment amount
```

- It pays oldest due invoices first by due date/id.
- The selected invoice is only the entry/reference invoice; it must not jump ahead of older due invoices.
- Any remaining amount after all due invoices are paid is stored in `customer.account_balance`.
- Every invoice payment portion must create a `payment_allocations` row.
- Every advance balance add/use must create a `customer_balance_transactions` row.
- Customer and active subscription are activated only when all invoice due is cleared.
- MikroTik sync is attempted after all due clears.

### bKash SMS

Important files:

- `routes/api.php`
- `app/Http/Controllers/BkashSmsPaymentController.php`
- `app/Services/BkashSmsPaymentService.php`
- `app/Models/BkashSmsPayment.php`
- `resources/views/bkash_sms_payments/*`

Webhook:

```text
POST /api/bkash/sms
Header: X-SMS-Token: us-bkash-sms-2026
Body: sender=..., message=...
```

Recommended JSON body from SmsForwarder:

```json
{
  "sender": "{{from}}",
  "message": "{{msg}}",
  "device_name": "{{device_name}}"
}
```

If the SMS app cannot send `device_name`, this app tries to parse the device name from the raw forwarded text. The final fallback is `sms_sender`.

Manual entry:

```text
/bkash-sms-payments/create
```

Matching rules:

1. Parse `Ref` from SMS.
2. If `Ref` exactly matches `customers.mikrotik_username` or `customers.connection_id`, use that customer.
3. If Ref is missing or does not match, search by sender mobile number.
4. If mobile search returns exactly one customer, use that customer.
5. If mobile search returns zero or multiple customers, keep the SMS `pending`.
6. Duplicate `TrxID` creates a new log with status `duplicate`, but must not update ledger, invoice, or customer balance.

SMS log stores raw SMS, amount, sender number, Ref, TrxID, status, customer, invoice, payment, and processing message.

bKash account and `entry_by` rules:

- The bKash sender/customer number must not become a `PaymentAccount`.
- `PaymentAccount` for SMS payments represents the phone/device that forwarded the SMS.
- Example: if the forwarded SMS device is `Anike Redmi`, create/use:

```text
payment_method: bkash
account_name: Anike Redmi
account_number: sms-device:anike-redmi
entry_by: Anike Redmi
entry_by_type: sms_device
```

- Web/manual user entries should use the logged-in user ID as `entry_by` and `entry_by_type=user`.
- System/backfilled records may use `entry_by=system` and `entry_by_type=system`.

### MikroTik

Important files:

- `app/Models/MikrotikRouter.php`
- `app/Http/Controllers/MikrotikRouterController.php`
- `app/Services/RouterOsClient.php`
- `app/Services/MikrotikCustomerSyncService.php`
- `resources/views/mikrotik_routers/*`

Router records include API login details, ping/API status history, PPPoE sync interval, inactive PPPoE profile, and last sync summary.

`/mikrotik-routers` checks both API login and ping. Ping online but API offline is valid, for example when the router responds to ICMP but TCP 8728 is blocked, refused, or not reachable through NAT.

PPPoE sync:

- Command: `php artisan mikrotik:sync-router-users`
- Force now: `php artisan mikrotik:sync-router-users --force`
- Run this from Windows Task Scheduler every minute; the command respects each router's own interval.
- Active customers use package `mikrotik_profile`.
- Inactive/due/no-package customers are not disabled.
- Inactive users are moved to the router's `inactive_pppoe_profile`.
- If a profile/status changes, remove the active PPP session from `/ppp/active` so the new profile applies after reconnect.
- If one router fails, sync should continue on other eligible routers.

### OLT ONU Inventory

Important files:

- `app/Models/OltDevice.php`
- `app/Models/OltOnu.php`
- `app/Http/Controllers/OltOnuController.php`
- `app/Services/OltSshClient.php`
- `app/Services/OltTelnetClient.php`
- `app/Services/OltLiveOutputParser.php`
- `resources/views/olt_onus/index.blade.php`
- `resources/views/olt_onus/create_olt.blade.php`

Routes:

```text
GET /olt-onus
GET /olt-onus/olts/create
POST /olt-onus/olts
POST /olt-onus/olts/{oltDevice}/refresh
```

Permission:

```text
manage_mikrotik_routers
```

The feature is for live data, not backup-file display. The app stores OLT access
credentials in `olt_devices`, then the refresh action connects to the OLT and
runs configured read-only commands.

Current known OLT access:

```text
Host/IP: 192.168.10.111
Access method: ssh
Port: 22
Username: isp_app
Password: secure credential source
```

The OLT offers legacy SSH host keys (`ssh-rsa`, `ssh-dss`). `OltSshClient` uses
phpseclib and explicitly prefers those host key algorithms so it can connect
like this manual command:

```bash
ssh -oHostKeyAlgorithms=+ssh-rsa -oPubkeyAcceptedAlgorithms=+ssh-rsa isp_app@192.168.10.111
```

Current HSGQ read context and live commands:

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

HSGQ EPON deny-list / blacklist behavior:

- On the current US_EPON firmware, `show black-onu all` is not supported in the expected context.
- Denied/blacklisted EPON ONUs are listed per PON interface with `show blacklist onu-info all`.
- The deny-list page must enter `config`, then scan the configured EPON ports in one OLT session:

```text
config
interface epon 1
show blacklist onu-info all
exit
interface epon 2
show blacklist onu-info all
exit
...
```

- Do not open one OLT connection per PON for the deny-list page. It is much slower than a PuTTY session because every connection repeats login and prompt setup.
- Known real blacklist row format:

```text
PON/ONU     Mac-Address        Blacklist_Reject_Count  Reason                  ONU-Name
7/1         70:a8:e3:f3:75:47  67                                              B_ONU07/01
```

- For US_EPON, SSH accepts login but loses spaces in interactive write/read commands such as `show onu-info all` and `bind-onu 0 mac ...`, producing broken commands like `show onu-infoall` and `bind-onu 0mac...`.
- EPON utility/read and write/add flows should use Telnet port `23` when the OLT record says SSH but the protocol profile is `hsgq_epon`.
- `OltTelnetClient` must handle HSGQ `--More--` pagination and use non-blocking socket reads. Blocking `fread()` waits until timeout after large command output and makes refresh look much slower than PuTTY.
- EPON `Refresh Live Data` uses a fast status-only path: it polls `show onu-info all` per configured PON, skips per-ONU alarms/VLAN detail, skips global MAC polling, and skips `show optical-info`. This keeps the OLT query close to PuTTY speed; optical power refresh should be a separate explicit slow/diagnostic action if reintroduced.
- EPON `Full Power/VLAN Refresh` is the explicit optical-power path. It still loops through selected `pon_ports`, but it now skips per-ONU alarm polling, reads the fast global `show mac-address epon all` table for learned MACs, and supports a `pon_port` request value so operators can refresh one PON at a time. On US_EPON, sampled optical timing was about 4-13 seconds per PON and about 49 seconds for all 8 PONs, because the OLT only exposes optical power as a per-PON CLI table.
- EPON VLAN polling via `show port-vlan` is only available inside `interface onu {pon}/{onu}` on this firmware. Bulk PON/global VLAN commands were not available, so the app keeps stored VLAN records from add/edit flows and only reads per-ONU VLAN detail for rows missing VLAN data.
- GPON `Refresh Live Data` must run `show ont-info all` in global/config context for this firmware. Running it inside `interface gpon 1` returns only a partial set. Fast GPON refresh skips service-port/MAC/alarm detail polling. Full Power/VLAN mode keeps status, optical, and service-port data but still skips `show mac-address all`, because MAC polling alone takes about 8 seconds after the Device MAC column was removed from the list.
- GPON learned MAC refresh is intentionally separate from normal/full refresh. The UI shows PON 1-16 for GPON MAC refresh and runs `show mac-address port gpon {pon}`. On US_GPON, this tested around 0.13 seconds for PON 1, while global `show mac-address all` took about 15 seconds.
- Refresh success/error flash messages include the elapsed OLT query time, for example `370 live ONU record(s) refreshed from US_EPON in 3.25 seconds`.
- EPON single-ONU refresh should keep `show onu-info all` inside the selected PON context and then filter the parsed result in PHP. Do not rewrite it to `show onu-info {onu_id}` for this firmware; that can return a different/unsupported format and make the UI report "current ONU was not found".
- The `/olt-onus` list supports per-row `Update Now` refresh. It calls `POST /olt-onus/{oltOnu}/refresh` with JSON headers, refreshes only that ONU, persists the DB row, and updates the row's status/MAC/VLAN/power/last-poll cells without refreshing all 370 ONUs.
- Opening an ONU details page runs one single-ONU refresh before rendering, unless `skip_auto_refresh=1` is present after a manual POST refresh redirect. The details page also has optional browser-side auto update with a user-selected interval; each tick refreshes the ONU and saves the DB.
- ONU operator notes are stored in `olt_onus.note` and can be edited from both the list row and the details page.
- OLT list/detail pages must not load or render huge raw output unless the operator explicitly needs it. The list query selects only table columns and defaults to 200 rows per page; the detail page shows bounded raw-output previews to avoid slow browser rendering.
- The shared layout displays server and browser page timing in the bottom-right corner. Keep this visible while diagnosing OLT page performance.
- When adding from the EPON deny-list, remove the MAC from blacklist before binding. The command is optional because a previous failed/retried add attempt may already have removed it:

```text
blacklist delete mac 70:a8:e3:f3:75:47
bind-onu {onu_id} mac {mac} onu-type 1ge name "{name}"
interface onu {pon_port}/{onu_id}
port-vlan {ethernet_port} mode tag {vlan} pri 0
save
```

The live parser tries to extract:

- PON port and ONU ID
- MAC address
- ONU online/offline status
- RX optical power in dBm
- distance if present
- description/name if present

Important limitation:

- OLT polling must remain read-only. `OltOnuController::firstUnsafeShowCommand()` blocks live commands that do not start with `show` or `display`, and blocks write/change words such as `set`, `add`, `delete`, `bind`, `save`, `reboot`, and `reset`.
- `OltOnuController::firstUnsafeContextCommand()` only permits CLI navigation needed for reading HSGQ data: `enable`, `config`/`configure`, `interface epon 1-8`, and `exit`.
- Full EPON refresh loops through the selected `pon_ports` and runs `interface epon N`, `show onu-info all`, then `show optical-info` for each selected PON. Prefer the UI PON selector for day-to-day optical updates.
- Do not add pager/helper/config commands that change OLT state. `OltSshClient` handles `--More--` pagination interactively without sending persistent config.
- The PHP server running the app must reach `192.168.10.111:22`. If production `finalaccess.com` is outside the LAN without VPN/routing, live OLT polling will fail from production.
- HSGQ command names vary by firmware; keep commands configurable on the OLT record.

HSGQ GPON ONU add/name caveat:

- The GPON OLT may add an ONT successfully but keep the default name like `ONT01/005`.
- Known rejected forms on this firmware include `interface ont 1/5`, `ont add 1 5 ...`, `ont add ... desc "name"` in the wrong argument position, and `ont modify 5 desc "name"`.
- Current add flow enters `interface gpon {pon_port}` and tries multiple `ont add` syntaxes, preferring variants that include `desc "requested name"` before falling back to a no-name add so service provisioning does not fail.
- After add/VLAN, the app queries `show ont-info all` and `show ont-info {onu_id}`. If the OLT still reports a default name, the app tries several optional rename/description commands and then queries again.
- Name repair is best-effort. Optional rename command failures must not fail ONU add/VLAN/save. The final OLT-reported name is stored back to the app so operators can see whether the OLT accepted the requested name.
- Auto-discovery defaults to showing all active OLTs serially. Do not run live ONU refresh automatically on page load because it is slow; use the page's manual refresh buttons.

## CWMP / TR-069 Future Module

This section documents the required plan for adding CWMP/TR-069 support. Do not try to implement TR-069 directly inside Laravel unless explicitly requested. Use a dedicated ACS server and let Laravel integrate with that server through API.

### What TR-069 Is For

TR-069, also called CWMP, is used for remote CPE/router management through an ACS (Auto Configuration Server).

Good use cases:

- Discover customer CPE devices automatically.
- Read serial number, OUI, product class, software version, WAN status, WiFi SSID, and other parameters.
- Reboot CPE.
- Push WiFi SSID/password changes.
- Push provisioning presets.
- Run diagnostics where supported.
- Track last inform/online status.

Do not replace the existing MikroTik PPPoE billing/profile sync with TR-069 unless there is a clear reason. For PPPoE package/profile activation, the current MikroTik API flow is still the better fit.

### Required Architecture

Recommended design:

```text
Customer CPE/router -> ACS server (GenieACS) -> Laravel app
```

Laravel should not be the ACS. Laravel should:

- Store device/customer mapping.
- Call the ACS API.
- Show device status and actions in customer pages.
- Keep an audit trail of CPE actions.

Recommended ACS:

```text
GenieACS
Docs: https://docs.genieacs.com/en/
```

MikroTik TR-069 client documentation:

```text
https://help.mikrotik.com/docs/spaces/ROS/pages/9863195/TR-069
```

### Network Requirements

The CPE must reach the ACS URL.

Examples:

```text
http://acs.example.com:7547
https://acs.example.com
```

For local testing:

```text
http://192.168.6.245:7547
```

Use a public domain or VPN for production. Do not expose an unsecured ACS to the internet without authentication, firewall rules, and HTTPS/reverse proxy where possible.

Common ports:

- `7547`: CWMP inform endpoint, often used by ACS.
- `3000`: GenieACS UI, if using default local setup.
- `7557`: GenieACS NBI/API, if using default local setup.

Restrict access to ACS UI/API by firewall/VPN. Customer CPEs only need the CWMP inform URL.

### MikroTik/CPE Setup

MikroTik devices need TR-069 client support/package depending on RouterOS version/device.

Conceptual MikroTik config:

```routeros
/tr069-client set enabled=yes acs-url=http://acs.example.com:7547
```

Also configure if needed:

- periodic inform interval
- ACS username/password
- connection request username/password
- NAT/firewall rules if connection request must work from ACS to CPE

Important: some CPE vendors use different parameter trees and may not expose every desired field/action.

### Laravel Database Plan

Add these tables when implementing the module.

`cwmp_devices`:

```text
id
entry_by
entry_by_type
customer_id nullable
acs_device_id unique
serial_number nullable index
oui nullable index
product_class nullable
manufacturer nullable
model_name nullable
software_version nullable
hardware_version nullable
ip_address nullable
mac_address nullable
status default unknown
last_inform_at nullable
last_sync_at nullable
last_error nullable
raw_summary json nullable
timestamps
```

`cwmp_device_parameters`:

```text
id
cwmp_device_id
parameter_name index
parameter_value text nullable
writable boolean default false
last_seen_at nullable
timestamps
```

`cwmp_actions`:

```text
id
entry_by
entry_by_type
cwmp_device_id
customer_id nullable
action
status default pending
request_payload json nullable
response_payload json nullable
error_message nullable
started_at nullable
finished_at nullable
timestamps
```

Optional later:

- `cwmp_presets`
- `cwmp_provisioning_rules`
- `cwmp_parameter_mappings`

### Laravel Service Plan

Create service:

```text
app/Services/GenieAcsClient.php
```

Responsibilities:

- HTTP client wrapper for GenieACS NBI/API.
- Search/list devices.
- Get device parameters.
- Refresh device task.
- Reboot device task.
- Set parameter values task.
- Handle API errors consistently.

Suggested `.env` keys:

```env
GENIEACS_API_URL=http://127.0.0.1:7557
GENIEACS_UI_URL=http://127.0.0.1:3000
GENIEACS_USERNAME=
GENIEACS_PASSWORD=
GENIEACS_TIMEOUT=10
```

Add config:

```text
config/services.php -> genieacs
```

### Laravel Routes And UI Plan

Add permission:

```text
manage_cwmp_devices
```

Routes:

```text
GET /cwmp-devices
GET /cwmp-devices/{device}
POST /cwmp-devices/sync
POST /cwmp-devices/{device}/refresh
POST /cwmp-devices/{device}/reboot
POST /cwmp-devices/{device}/set-parameters
POST /customers/{customer}/cwmp-devices/{device}/attach
POST /customers/{customer}/cwmp-devices/{device}/detach
```

UI:

- Add menu item under Network: `CWMP Devices`.
- Customer show page should display linked CPE/device section:
  - status
  - last inform
  - serial number
  - model/software version
  - reboot button
  - refresh button
  - WiFi SSID/password fields if supported

### Artisan Commands

Add:

```bash
php artisan cwmp:sync-devices
php artisan cwmp:refresh-device {device_id}
```

Schedule:

```text
cwmp:sync-devices every 5 minutes
```

The command should:

- Pull device list from ACS.
- Upsert `cwmp_devices`.
- Update `last_inform_at`, status, serial/OUI/product class.
- Link to customers by known serial number or manual mapping only. Do not auto-link by weak guesses.

### Security Rules

- Do not store ACS credentials in code.
- Use `.env`.
- Protect ACS UI/API by firewall/VPN/basic auth.
- Prefer HTTPS for ACS URL in production.
- Do not expose CPE connection request credentials in views.
- Log actions in `cwmp_actions`.
- Require permission for reboot/config changes.

### Implementation Order

1. Install and test GenieACS separately.
2. Connect one test CPE/router to ACS.
3. Confirm device appears in GenieACS UI.
4. Add Laravel `.env` and `config/services.php` entries.
5. Add migrations for `cwmp_devices`, `cwmp_device_parameters`, `cwmp_actions`.
6. Add `GenieAcsClient`.
7. Add sync command.
8. Add `/cwmp-devices` list/details pages.
9. Add customer-device attach/detach.
10. Add safe actions: refresh first, then reboot, then parameter set.
11. Add tests for sync mapping and action creation.

### Acceptance Checklist

- A test CPE can inform to ACS.
- Laravel can list ACS devices.
- Device can be linked to a customer.
- Last inform/status appears in Laravel.
- Reboot action is logged before and after execution.
- Failed ACS calls show error without crashing the page.
- No billing/MikroTik PPPoE behavior changes unless explicitly requested.
