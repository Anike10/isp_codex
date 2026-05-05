<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentAccount;
use Illuminate\Http\Request;

class PaymentAccountController extends Controller
{
    public function index()
    {
        $accounts = PaymentAccount::query()
            ->withSum('payments as collected_amount', 'amount')
            ->orderBy('payment_method')
            ->orderBy('account_name')
            ->get();

        $cashCollected = Payment::where('payment_method', 'cash')->sum('amount');
        $methodTotals = Payment::query()
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->pluck('total', 'payment_method');

        return view('payment_accounts.index', compact('accounts', 'cashCollected', 'methodTotals'));
    }

    public function create()
    {
        return view('payment_accounts.create');
    }

    public function show(PaymentAccount $paymentAccount)
    {
        $paymentAccount->load([
            'payments' => fn ($query) => $query->with(['customer', 'invoice'])->orderBy('payment_date')->orderBy('id'),
        ]);

        return view('payment_accounts.show', compact('paymentAccount'));
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
