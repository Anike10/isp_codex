<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        return view('payments.index', [
            'payments' => Payment::with(['customer', 'invoice', 'account'])
                ->latest()
                ->paginate($this->perPage($request))
                ->appends($request->query()),
        ]);
    }

    public function create()
    {
        return view('payments.create', [
            'invoices' => Invoice::with('customer')->where('due_amount', '>', 0)->latest()->get(),
            'paymentAccounts' => PaymentAccount::where('status', 'active')->orderBy('payment_method')->orderBy('account_name')->get(),
        ]);
    }

    public function store(Request $request, PaymentService $paymentService)
    {
        $data = $request->validate([
            'invoice_id' => ['required', 'exists:invoices,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:cash,bkash,nagad,bank'],
            'payment_account_id' => ['nullable'],
            'new_account_name' => ['nullable', 'string', 'max:255'],
            'new_account_number' => ['nullable', 'string', 'max:100'],
            'payment_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        if ($data['payment_method'] === 'cash') {
            $data['payment_account_id'] = null;
        } elseif (($data['payment_account_id'] ?? null) === '__new__') {
            $request->validate([
                'new_account_name' => ['required', 'string', 'max:255'],
                'new_account_number' => ['required', 'string', 'max:100'],
            ]);

            $account = PaymentAccount::firstOrCreate(
                [
                    'payment_method' => $data['payment_method'],
                    'account_number' => $data['new_account_number'],
                ],
                [
                    'account_name' => $data['new_account_name'],
                    'opening_balance' => 0,
                    'status' => 'active',
                ]
            );

            $data['payment_account_id'] = $account->id;
        } else {
            $account = PaymentAccount::where('id', $data['payment_account_id'] ?? null)
                ->where('payment_method', $data['payment_method'])
                ->where('status', 'active')
                ->first();

            if (! $account) {
                return back()->withInput()->withErrors([
                    'payment_account_id' => 'Please select a valid account for this payment method or add a new account.',
                ]);
            }

            $data['payment_account_id'] = $account->id;
        }

        try {
            $paymentService->recordPayment(Invoice::findOrFail($data['invoice_id']), $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()->route('payments.index')->with('success', 'Payment recorded successfully.');
    }
}
