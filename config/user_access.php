<?php

return [
    'groups' => [
        'overview' => [
            'label' => 'Overview',
            'items' => [
                'view_dashboard' => ['label' => 'Dashboard', 'menus' => ['Dashboard']],
                'use_reseller_portal' => ['label' => 'Reseller Portal', 'menus' => ['Reseller Portal']],
            ],
        ],
        'network' => [
            'label' => 'Network',
            'items' => [
                'manage_packages' => ['label' => 'Packages', 'menus' => ['Packages']],
                'manage_mikrotik_routers' => [
                    'label' => 'MikroTik & OLT Tools',
                    'menus' => ['MikroTik Routers', 'IP Pools', 'FTTX Network Map', 'OLT ONUs', 'ONU Deny List', 'Auto Discovery', 'OLT Protocols & Profiles'],
                ],
            ],
        ],
        'billing' => [
            'label' => 'Billing',
            'items' => [
                'manage_customers' => ['label' => 'Parties', 'menus' => ['Parties']],
                'manage_resellers' => ['label' => 'Resellers', 'menus' => ['Resellers']],
                'manage_invoices' => [
                    'label' => 'Invoices & Quotations',
                    'menus' => ['Invoices', 'Create Invoice', 'Sale Returns', 'Quotations', 'Create Quotation', 'Payment Note Default', 'Organizations', 'Print History'],
                ],
                'finalize_invoices' => ['label' => 'Finalize Invoices', 'menus' => ['Invoice finalization action']],
                'manage_payments' => ['label' => 'Payments', 'menus' => ['Payments', 'bKash SMS']],
                'manage_payment_accounts' => ['label' => 'Accounts & Ledger', 'menus' => ['Payment Accounts', 'Accounting Ledger']],
                'manage_expenses' => ['label' => 'Employees & Expenses', 'menus' => ['Employees', 'Salary & Expenses', 'Expenses']],
            ],
        ],
        'support' => [
            'label' => 'Support',
            'items' => [
                'manage_tickets' => ['label' => 'Tickets', 'menus' => ['Tickets']],
            ],
        ],
        'inventory' => [
            'label' => 'Inventory',
            'items' => [
                'manage_products' => [
                    'label' => 'Inventory & Purchase',
                    'menus' => ['Products', 'In-house Use', 'Inventory Reports', 'Warehouses', 'Transfers', 'Categories', 'Purchase Bills'],
                ],
            ],
        ],
        'warranty' => [
            'label' => 'Warranty',
            'items' => [
                'view_warranty_claims' => ['label' => 'View Warranty Claims', 'menus' => ['Warranty Claims']],
                'manage_warranty_claims' => ['label' => 'Manage Warranty Claims', 'menus' => ['New Claim', 'Warranty claim actions']],
                'manage_service_products' => ['label' => 'Service Products', 'menus' => ['Service product actions']],
            ],
        ],
        'fleet' => [
            'label' => 'Fleet',
            'items' => [
                'manage_fleet' => ['label' => 'Fleet Management', 'menus' => ['Vehicles', 'Drivers', 'Trips', 'Fuel', 'Maintenance', 'Fleet Reports']],
            ],
        ],
        'admin' => [
            'label' => 'Admin',
            'items' => [
                'manage_users' => ['label' => 'Users & Roles', 'menus' => ['Users', 'Roles']],
                'download_backup' => ['label' => 'Database Backup', 'menus' => ['Download Backup']],
            ],
        ],
    ],
    'menu_groups' => [
        'overview' => [
            'label' => 'Main Menu',
            'items' => [
                'dashboard' => ['label' => 'Dashboard', 'permission' => 'view_dashboard', 'routes' => ['dashboard']],
                'reseller_portal' => ['label' => 'Reseller Portal', 'permission' => 'use_reseller_portal', 'routes' => ['reseller.*']],
            ],
        ],
        'network' => [
            'label' => 'Network',
            'items' => [
                'packages' => ['label' => 'Packages', 'permission' => 'manage_packages', 'routes' => ['packages.*']],
                'mikrotik_routers' => ['label' => 'MikroTik Routers', 'permission' => 'manage_mikrotik_routers', 'routes' => ['mikrotik-routers.*']],
                'ip_pools' => ['label' => 'IP Pools', 'permission' => 'manage_mikrotik_routers', 'routes' => ['ip-pools.*']],
                'network_map' => ['label' => 'FTTX Network Map', 'permission' => 'manage_mikrotik_routers', 'routes' => ['network-map.*']],
                'olt_onus' => ['label' => 'OLT ONUs', 'permission' => 'manage_mikrotik_routers', 'routes' => ['olt-onus.index', 'olt-onus.show', 'olt-onus.olts.*', 'olt-onus.refresh*', 'olt-onus.vlan.*', 'olt-onus.ethernet-port-state.*', 'olt-onus.name.*', 'olt-onus.description.*', 'olt-onus.note.*', 'olt-onus.notes.*']],
                'onu_deny_list' => ['label' => 'ONU Deny List', 'permission' => 'manage_mikrotik_routers', 'routes' => ['olt-onus.deny-list*']],
                'onu_auto_discovery' => ['label' => 'Auto Discovery List', 'permission' => 'manage_mikrotik_routers', 'routes' => ['olt-onus.auto-discovery*']],
                'olt_protocol_profiles' => ['label' => 'OLT Protocol/Profile', 'permission' => 'manage_mikrotik_routers', 'routes' => ['olt-onus.protocol-profiles.*']],
            ],
        ],
        'billing' => [
            'label' => 'Billing',
            'items' => [
                'parties' => ['label' => 'Parties', 'permission' => 'manage_customers', 'routes' => ['customers.*']],
                'resellers' => ['label' => 'Resellers', 'permission' => 'manage_resellers', 'routes' => ['resellers.*']],
                'invoices' => ['label' => 'Invoices', 'permission' => 'manage_invoices', 'routes' => ['invoices.index', 'invoices.show', 'invoices.edit', 'invoices.update', 'invoices.generate', 'invoices.copy-next-month', 'invoices.invoice', 'invoices.challan', 'invoices.quotation', 'invoices.delivery-challan']],
                'create_invoice' => ['label' => 'Create Invoice', 'permission' => 'manage_invoices', 'routes' => ['invoices.create', 'invoices.store', 'invoice-customers.*']],
                'sale_returns' => ['label' => 'Sale Returns', 'permission' => 'manage_invoices', 'routes' => ['sale-returns.*']],
                'quotations' => ['label' => 'Quotations', 'permission' => 'manage_invoices', 'routes' => ['quotations.index', 'quotations.show', 'quotations.edit', 'quotations.update', 'quotations.print', 'quotations.make-invoice']],
                'create_quotation' => ['label' => 'Create Quotation', 'permission' => 'manage_invoices', 'routes' => ['quotations.create', 'quotations.store']],
                'payment_note_default' => ['label' => 'Payment Note Default', 'permission' => 'manage_invoices', 'routes' => ['invoices.payment-note-default.*']],
                'organizations' => ['label' => 'Organizations', 'permission' => 'manage_invoices', 'routes' => ['organizations.*']],
                'print_history' => ['label' => 'Print History', 'permission' => 'manage_invoices', 'permissions' => ['manage_invoices', 'manage_payments', 'manage_expenses'], 'routes' => ['print-logs.index']],
                'payments' => ['label' => 'Payments', 'permission' => 'manage_payments', 'routes' => ['payments.*', 'invoices.pay-selected']],
                'bkash_sms' => ['label' => 'bKash SMS', 'permission' => 'manage_payments', 'routes' => ['bkash-sms-payments.*']],
                'payment_accounts' => ['label' => 'Payment Accounts', 'permission' => 'manage_payment_accounts', 'routes' => ['payment-accounts.*']],
                'accounting_ledger' => ['label' => 'Accounting Ledger', 'permission' => 'manage_payment_accounts', 'permissions' => ['manage_payment_accounts', 'manage_customers'], 'routes' => ['accounting.ledger*']],
                'employees' => ['label' => 'Employees', 'permission' => 'manage_expenses', 'routes' => ['employees.*']],
                'expenses' => ['label' => 'Salary & Expenses', 'permission' => 'manage_expenses', 'routes' => ['expenses.*']],
            ],
        ],
        'support' => [
            'label' => 'Support',
            'items' => [
                'tickets' => ['label' => 'Tickets', 'permission' => 'manage_tickets', 'routes' => ['tickets.*']],
            ],
        ],
        'inventory' => [
            'label' => 'Inventory',
            'items' => [
                'products' => ['label' => 'Products', 'permission' => 'manage_products', 'routes' => ['products.*']],
                'in_house_use' => ['label' => 'In-house Use', 'permission' => 'manage_products', 'routes' => ['in-house-use.index', 'in-house-use.store', 'in-house-use.show', 'in-house-use.returns.*', 'in-house-use.approval-document']],
                'employee_asset_report' => ['label' => 'Employee Asset Report', 'permission' => 'manage_products', 'routes' => ['in-house-use.report.employees']],
                'returned_used_stock' => ['label' => 'Returned Used Stock', 'permission' => 'manage_products', 'routes' => ['in-house-use.report.used-stock']],
                'in_house_history' => ['label' => 'In-house History', 'permission' => 'manage_products', 'routes' => ['in-house-use.report.history']],
                'warehouses' => ['label' => 'Warehouses', 'permission' => 'manage_products', 'routes' => ['warehouses.*']],
                'stock_transfer' => ['label' => 'Stock Transfer', 'permission' => 'manage_products', 'routes' => ['warehouse-transfers.*']],
                'stock_history' => ['label' => 'Stock History', 'permission' => 'manage_products', 'routes' => ['warehouse-movements.*']],
                'product_categories' => ['label' => 'Product Categories', 'permission' => 'manage_products', 'routes' => ['product-categories.*']],
                'purchase_bills' => ['label' => 'Purchase Bills', 'permission' => 'manage_products', 'routes' => ['purchase-bills.*']],
            ],
        ],
        'warranty' => [
            'label' => 'Warranty',
            'items' => [
                'warranty_claims' => ['label' => 'Warranty Claims', 'permission' => 'view_warranty_claims', 'permissions' => ['view_warranty_claims', 'manage_warranty_claims', 'manage_products'], 'routes' => ['warranty-claims.index', 'warranty-claims.show']],
                'new_warranty_claim' => ['label' => 'New Claim', 'permission' => 'manage_warranty_claims', 'permissions' => ['manage_warranty_claims', 'manage_products'], 'routes' => ['warranty-claims.create', 'warranty-claims.store', 'warranty-claims.status', 'warranty-claims.replace', 'warranty-claims.service-invoice']],
            ],
        ],
        'fleet' => [
            'label' => 'Fleet',
            'items' => [
                'fleet_vehicles' => ['label' => 'Vehicles', 'permission' => 'manage_fleet', 'routes' => ['fleet.index', 'fleet.show', 'fleet.update', 'fleet.assignments.*', 'fleet.maintenance-items.*', 'fleet.expenses.store', 'fleet.maintenance-logs.store']],
                'fleet_add_vehicle' => ['label' => 'Add Vehicle', 'permission' => 'manage_fleet', 'routes' => ['fleet.create', 'fleet.store']],
                'fleet_maintenance_schedules' => ['label' => 'Maintenance Schedules', 'permission' => 'manage_fleet', 'routes' => ['fleet.maintenance.schedules*']],
                'fleet_log_maintenance' => ['label' => 'Log Repair / Maintenance', 'permission' => 'manage_fleet', 'routes' => ['fleet.maintenance.logs.*', 'fleet.maintenance-logs.*']],
                'fleet_settings' => ['label' => 'Fleet Settings', 'permission' => 'manage_fleet', 'routes' => ['fleet.settings*']],
                'fleet_reports' => ['label' => 'All Fleet Reports', 'permission' => 'manage_fleet', 'routes' => ['fleet.reports']],
                'fleet_expense_report' => ['label' => 'Vehicle Expense Report', 'permission' => 'manage_fleet', 'routes' => ['fleet.reports.expenses', 'fleet.expenses.*']],
                'fleet_maintenance_report' => ['label' => 'Maintenance Report', 'permission' => 'manage_fleet', 'routes' => ['fleet.reports.maintenance']],
                'fleet_due_report' => ['label' => 'Due & Overdue Report', 'permission' => 'manage_fleet', 'routes' => ['fleet.reports.maintenance-due']],
                'fleet_duty_history' => ['label' => 'Staff Duty History', 'permission' => 'manage_fleet', 'routes' => ['fleet.reports.duty-history']],
            ],
        ],
        'admin' => [
            'label' => 'Admin',
            'items' => [
                'users' => ['label' => 'Users', 'permission' => 'manage_users', 'routes' => ['users.*']],
                'roles' => ['label' => 'Roles', 'permission' => 'manage_users', 'routes' => ['roles.*']],
                'database_backup' => ['label' => 'Download Backup', 'permission' => 'download_backup', 'routes' => ['backup.database']],
            ],
        ],
    ],
];
