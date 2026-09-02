<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\MikrotikImportedSecret;
use App\Models\Product;
use App\Models\SupportTicket;
use App\Services\MikrotikImportService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, MikrotikImportService $importService)
    {
        $unmanagedRouterUsers = collect();
        $unmanagedRouterUsersCheckedAt = null;

        if ($request->user()?->hasPermission('view_unmanaged_router_users')) {
            $unmanagedRouterUsers = $importService->unmanagedSecrets()
                ->groupBy(fn (MikrotikImportedSecret $secret) => $secret->router?->name ?? 'Unassigned router')
                ->map(fn ($secrets) => $secrets->values());
            $unmanagedRouterUsersCheckedAt = MikrotikImportedSecret::max('imported_at');
        }

        return view('dashboard', [
            'totalCustomers' => Customer::count(),
            'activeCustomers' => Customer::where('status', 'active')->count(),
            'monthlyIncome' => Invoice::where('billing_month', now()->format('Y-m'))->sum('paid_amount'),
            'totalDue' => Invoice::sum('due_amount'),
            'openTickets' => SupportTicket::whereIn('status', ['open', 'processing'])->count(),
            'lowStockProducts' => Product::whereColumn('stock_quantity', '<=', 'low_stock_alert')->count(),
            'recentInvoices' => Invoice::with('customer')->latest()->limit(5)->get(),
            'recentTickets' => SupportTicket::with('customer')->where('status', '!=', 'closed')->latest()->limit(5)->get(),
            'unmanagedRouterUsers' => $unmanagedRouterUsers,
            'unmanagedRouterUsersCheckedAt' => $unmanagedRouterUsersCheckedAt,
        ]);
    }
}
