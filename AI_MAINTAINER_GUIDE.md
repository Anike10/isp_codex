# AI Maintainer Guide

This guide is for another AI agent or developer who needs to update this Laravel project safely.

## Working Style

- Work like a highly skilled Laravel, PHP, and OLT engineer.
- Keep token and cost use low: inspect only the files needed, make focused changes, avoid repeated broad searches, and prefer concise verification.

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
- `resources/views/invoices/create.blade.php`: create and edit invoice form
- `resources/views/invoices/show.blade.php`: invoice details and final button
- `resources/views/invoices/challan.blade.php`: printable bill
- `resources/views/invoices/quotation.blade.php`: printable quotation
- `resources/views/invoices/delivery_challan.blade.php`: printable delivery challan
- `app/Services/BillingService.php`: monthly service bill generation
- `app/Services/PaymentService.php`: payment recording and invoice due update
- `app/Http/Controllers/PaymentAccountController.php`: account balances and ledger
- `app/Http/Controllers/OltOnuController.php`: OLT device setup, live refresh, and ONU inventory
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
- production deploy flow, server path, ownership, cache commands, migrations, cron, scheduler, webhook URLs, or backup/rollback process
- external integrations, `.env` keys, device connection methods, command names, ports, or known limitations
- tests, troubleshooting steps, or operational recovery instructions

Never commit real secrets to Markdown. Use placeholders and tell maintainers to get passwords/tokens from the approved secure source.

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
- Payment allocation summary, so one payment can be audited across multiple invoices.
- Advance balance credits and advance-used memo rows.

Important accounting rule:

- `payments` is the receipt row.
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
- When changing OLT/ONU behavior, OLT command sequences, parser rules, firmware-specific workarounds, migrations, troubleshooting findings, or operational decisions, update this guide in the same change. Do not wait for the operator to ask for documentation separately.

## Latest Business Rules

Read this section before changing billing, bKash SMS, customer status, or MikroTik code.

### Customer Identity And Balance

- Customer `connection_id` is the default ISP/MikroTik user ID.
- Customer `mikrotik_username` is displayed as User ID on `/customers`; if missing, use `connection_id`.
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
- Refresh loops through `pon_ports` and runs `interface epon N`, `show onu-info all`, then `show optical-info` for each selected PON.
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
