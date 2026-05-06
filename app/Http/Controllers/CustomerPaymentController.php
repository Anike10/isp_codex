<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PaymentAccount;
use App\Services\BillingService;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class CustomerPaymentController extends Controller
{
    public function create(Customer $customer)
    {
        return view('customers.payment', [
            'customer' => $customer->load('activeSubscription.package'),
            'dueInvoices' => $customer->invoices()
                ->where('due_amount', '>', 0)
                ->orderBy('due_date')
                ->orderBy('id')
                ->get(),
            'paymentAccounts' => PaymentAccount::where('status', 'active')->orderBy('payment_method')->orderBy('account_name')->get(),
        ]);
    }

    public function store(Request $request, Customer $customer, BillingService $billingService, PaymentService $paymentService)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:cash,bkash,nagad,bank'],
            'payment_account_id' => ['nullable', 'exists:payment_accounts,id'],
            'payment_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        if ($data['payment_method'] !== 'cash' && empty($data['payment_account_id'])) {
            return back()->withInput()->withErrors(['payment_account_id' => 'Please select an account for this payment method.']);
        }

        $invoice = $billingService->generateCurrentServiceBillForCustomer($customer)
            ?: $customer->invoices()->where('due_amount', '>', 0)->orderBy('due_date')->orderBy('id')->first();

        if (! $invoice) {
            return back()->withInput()->withErrors(['amount' => 'No active package or due invoice found for this customer.']);
        }

        $paymentService->recordPayment($invoice, $data);

        return redirect()->route('customers.show', $customer)->with('success', 'Customer payment recorded successfully.');
    }
}
