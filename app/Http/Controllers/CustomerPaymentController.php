<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentAccount;
use App\Services\BillingService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

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
            'balanceTransactions' => $customer->balanceTransactions()->latest()->limit(20)->get(),
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

        $invoice = $billingService->generateCurrentServiceBillForCustomer($customer);

        if (! $invoice || (float) $invoice->due_amount <= 0) {
            $invoice = $customer->invoices()->where('due_amount', '>', 0)->orderBy('due_date')->orderBy('id')->first();
        }

        if (! $invoice) {
            try {
                $paymentService->addAdvanceCredit($customer, $data);
            } catch (InvalidArgumentException $exception) {
                return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
            }

            return redirect()->route('customers.show', $customer)->with('success', 'No due invoice found. Payment was added to customer advance balance.');
        }

        try {
            $paymentService->recordPayment($invoice, $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()->route('customers.show', $customer)->with('success', 'Customer payment recorded successfully.');
    }

    public function createAdvance(Customer $customer)
    {
        return redirect()->route('customers.payments.create', $customer);
    }

    public function storeAdvance(Request $request, Customer $customer, PaymentService $paymentService)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:cash,bkash,nagad,bank'],
            'payment_account_id' => ['nullable', 'exists:payment_accounts,id'],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
        ]);

        if ($data['payment_method'] !== 'cash' && empty($data['payment_account_id'])) {
            return back()->withInput()->withErrors(['payment_account_id' => 'Please select an account for this payment method.']);
        }

        if ($data['payment_method'] !== 'cash') {
            $account = PaymentAccount::where('id', $data['payment_account_id'])
                ->where('payment_method', $data['payment_method'])
                ->where('status', 'active')
                ->first();

            if (! $account) {
                return back()->withInput()->withErrors(['payment_account_id' => 'Please select a valid account for this payment method.']);
            }
        }

        try {
            $paymentService->addAdvanceCredit($customer, $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()->route('customers.advance-payments.create', $customer)->with('success', 'Advance payment added successfully.');
    }

    public function applyAdvance(Request $request, Customer $customer, PaymentService $paymentService)
    {
        $data = $request->validate([
            'invoice_id' => [
                'required',
                Rule::exists('invoices', 'id')->where('customer_id', $customer->id),
            ],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
        ]);

        $invoice = Invoice::findOrFail($data['invoice_id']);

        try {
            $paymentService->applyAdvanceToInvoice($customer, $invoice, $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()->route('customers.advance-payments.create', $customer)->with('success', 'Advance balance applied to invoice successfully.');
    }
}
