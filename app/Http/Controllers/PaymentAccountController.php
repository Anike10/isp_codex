<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use App\Models\PaymentAccount;
use Illuminate\Http\Request;

class PaymentAccountController extends Controller
{
    public function index(Request $request)
    {
        $allAccounts = PaymentAccount::query()
            ->withSum('payments as collected_amount', 'amount')
            ->withSum('expenses as spent_amount', 'amount')
            ->orderBy('payment_method')
            ->orderBy('account_name')
            ->get();

        $accounts = PaymentAccount::query()
            ->withSum('payments as collected_amount', 'amount')
            ->withSum('expenses as spent_amount', 'amount')
            ->orderBy('payment_method')
            ->orderBy('account_name')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        $cashCollected = Payment::where('payment_method', 'cash')->sum('amount');
        $cashSpent = Expense::where('payment_method', 'cash')->sum('amount');
        $methodTotals = Payment::query()
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        return view('payment_accounts.index', compact('accounts', 'allAccounts', 'cashCollected', 'cashSpent', 'methodTotals'));
    }

    public function create()
    {
        return view('payment_accounts.create');
    }

    public function show(Request $request, PaymentAccount $paymentAccount)
    {
        $payments = $paymentAccount->payments()
            ->with(['customer', 'invoice'])
            ->orderBy('payment_date')
            ->orderBy('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        $totalCollected = $paymentAccount->payments()->sum('amount');
        $totalSpent = $paymentAccount->expenses()->sum('amount');

        return view('payment_accounts.show', compact('paymentAccount', 'payments', 'totalCollected', 'totalSpent'));
    }

    public function cashLedger(Request $request)
    {
        $payments = Payment::query()
            ->with(['customer', 'invoice'])
            ->where('payment_method', 'cash')
            ->orderBy('payment_date')
            ->orderBy('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());

        $totalCollected = Payment::where('payment_method', 'cash')->sum('amount');
        $totalSpent = Expense::where('payment_method', 'cash')->sum('amount');

        return view('payment_accounts.cash_ledger', compact('payments', 'totalCollected', 'totalSpent'));
    }

    public function store(Request $request)
    {
        PaymentAccount::create($request->validate([
            'payment_method' => ['required', 'in:bkash,nagad,bank'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'opening_balance' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'in:active,inactive'],
        ]));

        return redirect()->route('payment-accounts.index')->with('success', 'Payment account created successfully.');
    }
}
