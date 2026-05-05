<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SupportTicket;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard', [
            'totalCustomers' => Customer::count(),
            'activeCustomers' => Customer::where('status', 'active')->count(),
            'monthlyIncome' => Invoice::where('billing_month', now()->format('Y-m'))->sum('paid_amount'),
            'totalDue' => Invoice::sum('due_amount'),
            'openTickets' => SupportTicket::whereIn('status', ['open', 'processing'])->count(),
            'lowStockProducts' => Product::whereColumn('stock_quantity', '<=', 'low_stock_alert')->count(),
            'recentInvoices' => Invoice::with('customer')->latest()->limit(5)->get(),
            'recentTickets' => SupportTicket::with('customer')->latest()->limit(5)->get(),
        ]);
    }
}
