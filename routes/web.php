<?php

use App\Http\Controllers\AccountingLedgerController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BkashSmsPaymentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FleetController;
use App\Http\Controllers\FleetMaintenanceController;
use App\Http\Controllers\FleetOperationController;
use App\Http\Controllers\FleetReportController;
use App\Http\Controllers\InHouseUseController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MikrotikRouterController;
use App\Http\Controllers\MikrotikImportController;
use App\Http\Controllers\MikrotikRouterDataController;
use App\Http\Controllers\NetworkMapController;
use App\Http\Controllers\OltOnuController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\PaymentAccountController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PrintLogController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseBillController;
use App\Http\Controllers\QuotationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SaleReturnController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarrantyClaimController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->middleware('permission:view_dashboard')->name('dashboard');

    Route::middleware('permission:manage_customers')->group(function () {
        Route::get('customers/{customer}/payments/create', [CustomerPaymentController::class, 'create'])->name('customers.payments.create');
        Route::post('customers/{customer}/payments', [CustomerPaymentController::class, 'store'])->name('customers.payments.store');
        Route::get('customers/{customer}/advance-payments/create', [CustomerPaymentController::class, 'createAdvance'])->name('customers.advance-payments.create');
        Route::post('customers/{customer}/advance-payments', [CustomerPaymentController::class, 'storeAdvance'])->name('customers.advance-payments.store');
        Route::post('customers/{customer}/advance-payments/apply', [CustomerPaymentController::class, 'applyAdvance'])->name('customers.advance-payments.apply');
        Route::post('customers/{customer}/grace-period', [CustomerController::class, 'grantGracePeriod'])->name('customers.grace-period');
        Route::post('customers/{customer}/service-validity', [CustomerController::class, 'updateServiceValidity'])->name('customers.service-validity.update');
        Route::resource('customers', CustomerController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    });

    Route::middleware('permission:manage_packages')->group(function () {
        Route::patch('packages/{package}/inline', [PackageController::class, 'inlineUpdate'])->name('packages.inline-update');
        Route::resource('packages', PackageController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);
    });

    Route::middleware('permission:manage_invoices')->group(function () {
        Route::resource('organizations', OrganizationController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::get('quotations/{quotation}/print', [QuotationController::class, 'print'])->name('quotations.print');
        Route::post('quotations/{quotation}/make-invoice', [InvoiceController::class, 'makeFromQuotation'])->name('quotations.make-invoice');
        Route::resource('quotations', QuotationController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update']);
        Route::get('invoices/payment-note-default', [InvoiceController::class, 'editPaymentNoteDefault'])->name('invoices.payment-note-default.edit');
        Route::put('invoices/payment-note-default', [InvoiceController::class, 'updatePaymentNoteDefault'])->name('invoices.payment-note-default.update');
        Route::resource('invoices', InvoiceController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update']);
        Route::get('invoice-customers/search', [InvoiceController::class, 'searchCustomers'])->name('invoice-customers.search');
        Route::post('invoices/generate', [InvoiceController::class, 'generate'])->name('invoices.generate');
        Route::post('invoices/{invoice}/copy-next-month', [InvoiceController::class, 'copyForNextMonth'])->name('invoices.copy-next-month');
        Route::get('invoices/{invoice}/invoice', [InvoiceController::class, 'challan'])->name('invoices.invoice');
        Route::get('invoices/{invoice}/challan', fn ($invoice) => redirect()->route('invoices.invoice', $invoice))->name('invoices.challan');
        Route::get('invoices/{invoice}/quotation', [InvoiceController::class, 'quotation'])->name('invoices.quotation');
        Route::get('invoices/{invoice}/delivery-challan', [InvoiceController::class, 'deliveryChallan'])->name('invoices.delivery-challan');
        Route::resource('sale-returns', SaleReturnController::class)->only(['index', 'create', 'store', 'show']);
    });

    Route::middleware('permission:manage_invoices,manage_payments,manage_expenses')->group(function () {
        Route::get('print-history', [PrintLogController::class, 'index'])->name('print-logs.index');
        Route::post('print-history', [PrintLogController::class, 'store'])->name('print-logs.store');
    });

    Route::post('invoices/{invoice}/finalize', [InvoiceController::class, 'finalize'])
        ->middleware('permission:finalize_invoices')
        ->name('invoices.finalize');
    Route::post('invoices/finalize-selected', [InvoiceController::class, 'finalizeSelected'])
        ->middleware('permission:finalize_invoices')
        ->name('invoices.finalize-selected');
    Route::post('invoices/pay-selected', [InvoiceController::class, 'paySelected'])
        ->middleware('permission:manage_payments')
        ->name('invoices.pay-selected');

    Route::middleware('permission:manage_payments')->group(function () {
        Route::get('payments/{payment}/voucher', [PaymentController::class, 'voucher'])->name('payments.voucher');
        Route::get('payments/{payment}/thermal-voucher', [PaymentController::class, 'thermalVoucher'])->name('payments.thermal-voucher');
        Route::resource('payments', PaymentController::class)->only(['index', 'show', 'create', 'store']);
        Route::get('bkash-sms-payments', [BkashSmsPaymentController::class, 'index'])->name('bkash-sms-payments.index');
        Route::get('bkash-sms-payments/create', [BkashSmsPaymentController::class, 'create'])->name('bkash-sms-payments.create');
        Route::post('bkash-sms-payments', [BkashSmsPaymentController::class, 'manualStore'])->name('bkash-sms-payments.store');
        Route::post('bkash-sms-payments/{bkashSmsPayment}/approve', [BkashSmsPaymentController::class, 'approve'])->name('bkash-sms-payments.approve');
        Route::get('bkash-sms-payments/{bkashSmsPayment}', [BkashSmsPaymentController::class, 'show'])->name('bkash-sms-payments.show');
    });

    Route::get('accounting/ledger', [AccountingLedgerController::class, 'index'])
        ->middleware('permission:manage_payment_accounts,manage_customers')
        ->name('accounting.ledger');

    Route::middleware('permission:manage_payment_accounts')->group(function () {
        Route::get('payment-accounts/cash/ledger', [PaymentAccountController::class, 'cashLedger'])->name('payment-accounts.cash-ledger');
        Route::resource('payment-accounts', PaymentAccountController::class)->only(['index', 'show', 'create', 'store']);
    });

    Route::middleware('permission:manage_expenses')->group(function () {
        Route::post('employees/{employee}/salary-revisions', [EmployeeController::class, 'storeSalaryRevision'])->name('employees.salary-revisions.store');
        Route::get('employees/{employee}/balance-sheet', [EmployeeController::class, 'balanceSheet'])->name('employees.balance-sheet');
        Route::resource('employees', EmployeeController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::get('expenses/{expense}/voucher', [ExpenseController::class, 'voucher'])->name('expenses.voucher');
        Route::resource('expenses', ExpenseController::class)->only(['index', 'create', 'store', 'show']);
    });

    Route::middleware('permission:manage_fleet')->group(function () {
        Route::get('fleet/reports', [FleetReportController::class, 'index'])->name('fleet.reports');
        Route::get('fleet/reports/expenses', [FleetReportController::class, 'expenses'])->name('fleet.reports.expenses');
        Route::get('fleet/reports/maintenance', [FleetReportController::class, 'maintenance'])->name('fleet.reports.maintenance');
        Route::get('fleet/reports/maintenance-due', [FleetReportController::class, 'maintenanceDue'])->name('fleet.reports.maintenance-due');
        Route::get('fleet/reports/duty-history', [FleetReportController::class, 'dutyHistory'])->name('fleet.reports.duty-history');
        Route::get('fleet/maintenance/schedules', [FleetMaintenanceController::class, 'schedules'])->name('fleet.maintenance.schedules');
        Route::post('fleet/maintenance/schedules', [FleetMaintenanceController::class, 'storeSchedule'])->name('fleet.maintenance.schedules.store');
        Route::get('fleet/maintenance/logs/create', [FleetMaintenanceController::class, 'createLog'])->name('fleet.maintenance.logs.create');
        Route::post('fleet/maintenance/logs', [FleetMaintenanceController::class, 'storeLog'])->name('fleet.maintenance.logs.store');
        Route::get('fleet/maintenance/logs/{maintenanceLog}/edit', [FleetOperationController::class, 'editMaintenanceLog'])->name('fleet.maintenance-logs.edit');
        Route::put('fleet/maintenance/logs/{maintenanceLog}', [FleetOperationController::class, 'updateMaintenanceLog'])->name('fleet.maintenance-logs.update');
        Route::post('fleet/maintenance/logs/{maintenanceLog}/finalize', [FleetOperationController::class, 'finalizeMaintenanceLog'])->name('fleet.maintenance-logs.finalize');
        Route::get('fleet/maintenance/logs/{maintenanceLog}', [FleetOperationController::class, 'showMaintenanceLog'])->name('fleet.maintenance-logs.show');
        Route::get('fleet/settings', [FleetMaintenanceController::class, 'settings'])->name('fleet.settings');
        Route::post('fleet/settings', [FleetMaintenanceController::class, 'updateSettings'])->name('fleet.settings.update');
        Route::get('fleet/maintenance/photos/{photo}', [FleetMaintenanceController::class, 'photo'])->name('fleet.maintenance.photos.show');
        Route::get('fleet/expenses/{expense}/edit', [FleetOperationController::class, 'editExpense'])->name('fleet.expenses.edit');
        Route::put('fleet/expenses/{expense}', [FleetOperationController::class, 'updateExpense'])->name('fleet.expenses.update');
        Route::post('fleet/expenses/{expense}/finalize', [FleetOperationController::class, 'finalizeExpense'])->name('fleet.expenses.finalize');
        Route::get('fleet/expenses/{expense}', [FleetOperationController::class, 'showExpense'])->name('fleet.expenses.show');
        Route::post('fleet/{vehicle}/maintenance-items', [FleetOperationController::class, 'storeMaintenanceItem'])->name('fleet.maintenance-items.store');
        Route::post('fleet/{vehicle}/maintenance-logs', [FleetOperationController::class, 'storeMaintenanceLog'])->name('fleet.maintenance-logs.store');
        Route::post('fleet/{vehicle}/assignments', [FleetOperationController::class, 'storeAssignment'])->name('fleet.assignments.store');
        Route::patch('fleet/assignments/{assignment}/end', [FleetOperationController::class, 'endAssignment'])->name('fleet.assignments.end');
        Route::post('fleet/{vehicle}/expenses', [FleetOperationController::class, 'storeExpense'])->name('fleet.expenses.store');
        Route::resource('fleet', FleetController::class)->parameters(['fleet' => 'vehicle'])->only(['index', 'create', 'store', 'show', 'update']);
    });

    Route::middleware('permission:manage_mikrotik_routers')->group(function () {
        Route::get('ip-pools', [MikrotikRouterDataController::class, 'globalPools'])->name('ip-pools.index');
        Route::get('network-map', [NetworkMapController::class, 'show'])->name('network-map.index');
        Route::get('network-map/features', [NetworkMapController::class, 'index'])->name('network-map.features.index');
        Route::post('network-map/features', [NetworkMapController::class, 'store'])->name('network-map.features.store');
        Route::post('network-map/photos', [NetworkMapController::class, 'uploadPhotos'])->name('network-map.photos.store');
        Route::get('olt-onus/olts/create', [OltOnuController::class, 'createOlt'])->name('olt-onus.olts.create');
        Route::post('olt-onus/olts', [OltOnuController::class, 'storeOlt'])->name('olt-onus.olts.store');
        Route::get('olt-onus/olts/{oltDevice}/edit', [OltOnuController::class, 'editOlt'])->name('olt-onus.olts.edit');
        Route::put('olt-onus/olts/{oltDevice}', [OltOnuController::class, 'updateOlt'])->name('olt-onus.olts.update');
        Route::post('olt-onus/olts/{oltDevice}/apply-profile-defaults', [OltOnuController::class, 'applyProfileDefaults'])->name('olt-onus.olts.apply-profile-defaults');
        Route::delete('olt-onus/olts/{oltDevice}/cached-onus', [OltOnuController::class, 'clearCachedOnus'])->name('olt-onus.olts.cached-onus.destroy');
        Route::delete('olt-onus/olts/{oltDevice}', [OltOnuController::class, 'destroyOlt'])->name('olt-onus.olts.destroy');
        Route::post('olt-onus/olts/{oltDevice}/refresh', [OltOnuController::class, 'refresh'])->name('olt-onus.olts.refresh');
        Route::get('olt-onus/refresh-runs/{oltRefreshRun}', [OltOnuController::class, 'refreshRunStatus'])->name('olt-onus.refresh-runs.show');
        Route::post('olt-onus/olts/{oltDevice}/refresh-auto-discovery', [OltOnuController::class, 'refreshForAutoDiscovery'])->name('olt-onus.olts.refresh-auto-discovery');
        Route::post('olt-onus/olts/{oltDevice}/save-config', [OltOnuController::class, 'saveOltConfig'])->name('olt-onus.olts.save-config');
        Route::post('olt-onus/olts/{oltDevice}/config-backup', [OltOnuController::class, 'downloadConfigBackup'])->name('olt-onus.olts.config-backup');
        Route::get('olt-onus/deny-list', [OltOnuController::class, 'denyList'])->name('olt-onus.deny-list');
        Route::delete('olt-onus/deny-list/entry', [OltOnuController::class, 'deleteDenyListEntry'])->name('olt-onus.deny-list.destroy');
        Route::get('olt-onus/auto-discovery', [OltOnuController::class, 'autoDiscoveryList'])->name('olt-onus.auto-discovery');
        Route::post('olt-onus/auto-discovery/add', [OltOnuController::class, 'addDiscoveredOnu'])->name('olt-onus.auto-discovery.add');
        Route::get('olt-onus/protocol-profiles', [OltOnuController::class, 'protocolProfiles'])->name('olt-onus.protocol-profiles.index');
        Route::get('olt-onus/protocol-profiles/create', [OltOnuController::class, 'createProtocolProfile'])->name('olt-onus.protocol-profiles.create');
        Route::post('olt-onus/protocol-profiles', [OltOnuController::class, 'storeProtocolProfile'])->name('olt-onus.protocol-profiles.store');
        Route::get('olt-onus/protocol-profiles/{oltProtocolProfile}/edit', [OltOnuController::class, 'editProtocolProfile'])->name('olt-onus.protocol-profiles.edit');
        Route::put('olt-onus/protocol-profiles/{oltProtocolProfile}', [OltOnuController::class, 'updateProtocolProfile'])->name('olt-onus.protocol-profiles.update');
        Route::post('olt-onus/notes/current-laser', [OltOnuController::class, 'appendCurrentLaserToAllNotes'])->name('olt-onus.notes.current-laser.store');
        Route::patch('olt-onus/{oltOnu}/vlan', [OltOnuController::class, 'updateVlan'])->name('olt-onus.vlan.update');
        Route::patch('olt-onus/{oltOnu}/ethernet-port-state', [OltOnuController::class, 'updateEthernetPortState'])->name('olt-onus.ethernet-port-state.update');
        Route::patch('olt-onus/{oltOnu}/name', [OltOnuController::class, 'updateName'])->name('olt-onus.name.update');
        Route::patch('olt-onus/{oltOnu}/description', [OltOnuController::class, 'updateDescription'])->name('olt-onus.description.update');
        Route::patch('olt-onus/{oltOnu}/note', [OltOnuController::class, 'updateNote'])->name('olt-onus.note.update');
        Route::post('olt-onus/{oltOnu}/note/current-laser', [OltOnuController::class, 'appendCurrentLaserToNote'])->name('olt-onus.note.current-laser.store');
        Route::post('olt-onus/{oltOnu}/refresh', [OltOnuController::class, 'refreshOnu'])->name('olt-onus.refresh');
        Route::get('olt-onus/{oltOnu}', [OltOnuController::class, 'show'])->name('olt-onus.show');
        Route::get('olt-onus', [OltOnuController::class, 'index'])->name('olt-onus.index');
        Route::get('mikrotik-routers/{mikrotikRouter}/connection-status', [MikrotikRouterController::class, 'connectionStatus'])->name('mikrotik-routers.connection-status');
        Route::post('mikrotik-routers/{mikrotikRouter}/import/profiles', [MikrotikImportController::class, 'importProfiles'])->name('mikrotik-routers.import.profiles');
        Route::post('mikrotik-routers/{mikrotikRouter}/import/ip-pools', [MikrotikImportController::class, 'importIpPools'])->name('mikrotik-routers.import.ip-pools');
        Route::post('mikrotik-routers/{mikrotikRouter}/import/secrets', [MikrotikImportController::class, 'importSecrets'])->name('mikrotik-routers.import.secrets');
        Route::get('mikrotik-routers/{mikrotikRouter}/profiles', [MikrotikRouterDataController::class, 'profiles'])->name('mikrotik-routers.profiles.index');
        Route::get('mikrotik-routers/{mikrotikRouter}/pools', [MikrotikRouterDataController::class, 'pools'])->name('mikrotik-routers.pools.index');
        Route::post('mikrotik-routers/{mikrotikRouter}/pools', [MikrotikRouterDataController::class, 'createPool'])->name('mikrotik-routers.pools.store');
        Route::get('mikrotik-routers/{mikrotikRouter}/pools/import-live', fn (\App\Models\MikrotikRouter $mikrotikRouter) => redirect()->route('mikrotik-routers.pools.index', $mikrotikRouter)->with('warning', 'Use Import to App beside the required MikroTik pool.'))->name('mikrotik-routers.pools.import-live.help');
        Route::post('mikrotik-routers/{mikrotikRouter}/pools/import-live', [MikrotikRouterDataController::class, 'importLivePool'])->name('mikrotik-routers.pools.import-live');
        Route::post('mikrotik-routers/{mikrotikRouter}/secrets/import-as-party', [MikrotikRouterDataController::class, 'importLiveSecretAsParty'])->name('mikrotik-routers.secrets.import-as-party');
        Route::patch('mikrotik-routers/{mikrotikRouter}/pools/{appIpPool}', [MikrotikRouterDataController::class, 'updatePool'])->name('mikrotik-routers.pools.update');
        Route::delete('mikrotik-routers/{mikrotikRouter}/pools/{appIpPool}', [MikrotikRouterDataController::class, 'deletePool'])->name('mikrotik-routers.pools.destroy');
        Route::post('mikrotik-routers/{mikrotikRouter}/pools/{appIpPool}/export', [MikrotikRouterDataController::class, 'exportPool'])->name('mikrotik-routers.pools.export');
        Route::post('mikrotik-routers/{mikrotikRouter}/imported-pools/{mikrotikImportedIpPool}/save-to-app', [MikrotikRouterDataController::class, 'saveImportedPool'])->name('mikrotik-routers.imported-pools.save-to-app');
        Route::get('mikrotik-routers/{mikrotikRouter}/compare', [MikrotikRouterDataController::class, 'compare'])->name('mikrotik-routers.compare');
        Route::patch('mikrotik-routers/{mikrotikRouter}/compare/inline', [MikrotikRouterDataController::class, 'inlineUpdate'])->name('mikrotik-routers.compare.inline-update');
        Route::post('mikrotik-routers/{mikrotikRouter}/profiles/{package}/export', [MikrotikRouterDataController::class, 'exportProfile'])->name('mikrotik-routers.profiles.export');
        Route::post('mikrotik-routers/{mikrotikRouter}/customers/{customer}/export', [MikrotikRouterDataController::class, 'exportCustomer'])->name('mikrotik-routers.customers.export');
        Route::delete('mikrotik-routers/{mikrotikRouter}/router-extra', [MikrotikRouterDataController::class, 'deleteRouterExtra'])->name('mikrotik-routers.router-extra.destroy');
        Route::get('mikrotik-routers/{mikrotikRouter}/imported-secrets', [MikrotikImportController::class, 'secrets'])->name('mikrotik-routers.imported-secrets.index');
        Route::get('mikrotik-routers/{mikrotikRouter}/imported-secrets/create-parties', fn (\App\Models\MikrotikRouter $mikrotikRouter) => redirect()->route('mikrotik-routers.imported-secrets.index', $mikrotikRouter)->with('warning', 'Select PPPoE secrets from the list, then use Create Selected Parties.'))->name('mikrotik-routers.imported-secrets.create-parties.help');
        Route::patch('mikrotik-routers/{mikrotikRouter}/imported-secrets/{mikrotikImportedSecret}', [MikrotikImportController::class, 'updateSecret'])->name('mikrotik-routers.imported-secrets.update');
        Route::post('mikrotik-routers/{mikrotikRouter}/imported-secrets/create-parties', [MikrotikImportController::class, 'createParties'])->name('mikrotik-routers.imported-secrets.create-parties');
        Route::resource('mikrotik-routers', MikrotikRouterController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy']);
    });

    Route::middleware('permission:manage_tickets')->group(function () {
        Route::resource('tickets', TicketController::class)->only(['index', 'show', 'create', 'store']);
    });

    Route::middleware('permission:manage_products')->group(function () {
        Route::get('in-house-use/reports/employees', [InHouseUseController::class, 'employeeReport'])->name('in-house-use.report.employees');
        Route::get('in-house-use/reports/used-stock', [InHouseUseController::class, 'usedStockReport'])->name('in-house-use.report.used-stock');
        Route::get('in-house-use/reports/history', [InHouseUseController::class, 'historyReport'])->name('in-house-use.report.history');
        Route::get('in-house-use/{inHouseUse}/approval-document', [InHouseUseController::class, 'approvalDocument'])->name('in-house-use.approval-document');
        Route::post('in-house-use/{inHouseUse}/returns', [InHouseUseController::class, 'storeReturn'])->name('in-house-use.returns.store');
        Route::resource('in-house-use', InHouseUseController::class)->only(['index', 'store', 'show'])->parameters(['in-house-use' => 'inHouseUse']);
        Route::get('warehouse-movements', [WarehouseController::class, 'movements'])->name('warehouse-movements.index');
        Route::get('warehouse-transfers/create', [WarehouseController::class, 'createTransfer'])->name('warehouse-transfers.create');
        Route::post('warehouse-transfers', [WarehouseController::class, 'storeTransfer'])->name('warehouse-transfers.store');
        Route::resource('warehouses', WarehouseController::class)->only(['index', 'store', 'show']);
        Route::resource('product-categories', ProductCategoryController::class)->only(['index', 'store']);
        Route::post('purchase-bills/{purchaseBill}/finalize', [PurchaseBillController::class, 'finalize'])->name('purchase-bills.finalize');
        Route::get('purchase-bills/{purchaseBill}/document', [PurchaseBillController::class, 'document'])->name('purchase-bills.document');
        Route::resource('purchase-bills', PurchaseBillController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
        Route::resource('products', ProductController::class)->only(['index', 'show', 'create', 'store', 'edit', 'update']);
        Route::post('products/{product}/stock', [ProductController::class, 'moveStock'])->name('products.stock');
    });

    Route::middleware('permission:view_warranty_claims,manage_warranty_claims,manage_products')->group(function () {
        Route::get('warranty-claims', [WarrantyClaimController::class, 'index'])->name('warranty-claims.index');
    });

    Route::middleware('permission:manage_warranty_claims,manage_products')->group(function () {
        Route::get('warranty-claims/create', [WarrantyClaimController::class, 'create'])->name('warranty-claims.create');
        Route::post('warranty-claims', [WarrantyClaimController::class, 'store'])->name('warranty-claims.store');
        Route::post('warranty-claims/{warrantyClaim}/status', [WarrantyClaimController::class, 'updateStatus'])->name('warranty-claims.status');
        Route::post('warranty-claims/{warrantyClaim}/replace', [WarrantyClaimController::class, 'replace'])->name('warranty-claims.replace');
        Route::post('warranty-claims/{warrantyClaim}/service-invoice', [WarrantyClaimController::class, 'createServiceInvoice'])->name('warranty-claims.service-invoice');
    });

    Route::get('warranty-claims/{warrantyClaim}', [WarrantyClaimController::class, 'show'])
        ->middleware('permission:view_warranty_claims,manage_warranty_claims,manage_products')
        ->name('warranty-claims.show');

    Route::middleware('permission:manage_users')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::resource('roles', RoleController::class)->only(['index', 'create', 'store', 'edit', 'update']);
    });

    Route::get('backup/database', [DatabaseBackupController::class, 'download'])
        ->middleware('permission:download_backup')
        ->name('backup.database');
});
