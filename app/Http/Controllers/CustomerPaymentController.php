<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentAccount;
use App\Services\BillingService;
use App\Services\PaymentAccountPreferenceService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class CustomerPaymentController extends Controller
{
    public function create(Request $request, Customer $customer, PaymentAccountPreferenceService $preferenceService)
    {
        return view('customers.payment', [
            'customer' => $customer->load('activeSubscription.package'),
            'dueInvoices' => $customer->invoices()
                ->where('due_amount', '>', 0)
                ->orderBy('due_date')
                ->orderBy('id')
                ->get(),
            'balanceTransactions' => $customer->balanceTransactions()
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->limit(20)
                ->get(),
            'paymentAccounts' => PaymentAccount::where('status', 'active')->orderBy('payment_method')->orderBy('account_name')->get(),
            'paymentDefault' => $preferenceService->forUser($request->user()),
        ]);
    }

    public function store(Request $request, Customer $customer, BillingService $billingService, PaymentService $paymentService, AdvanceRenewalService $advanceRenewalService, PaymentAccountPreferenceService $preferenceService)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:cash,bkash,nagad,bank'],
            'payment_account_id' => ['nullable', 'exists:payment_accounts,id'],
            'payment_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'keep_as_advance' => ['nullable', 'in:1'],
            'invoice_allocations' => ['nullable', 'array'],
            'invoice_allocations.*' => ['nullable', 'numeric', 'min:0'],
            'set_as_default' => ['nullable', 'boolean'],
        ]);
        $rememberAsDefault = $request->boolean('set_as_default');
        unset($data['set_as_default']);

        $this->logSubmittedAmount($request, $customer, $data, 'customer_payment');
        $keepAsAdvance = (string) ($data['keep_as_advance'] ?? '') === '1';
        $invoiceAllocations = array_filter(
            $request->input('invoice_allocations', []),
            static fn ($amount) => (float) $amount > 0
        );

        if ($data['payment_method'] === 'cash') {
            $data['payment_account_id'] = null;
        } elseif (empty($data['payment_account_id'])) {
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

        $billingService->generateCurrentServiceBillForCustomer($customer);
        $invoice = $customer->invoices()->where('due_amount', '>', 0)->orderBy('due_date')->orderBy('id')->first();

        if ($keepAsAdvance) {
            try {
                if ($invoiceAllocations) {
                    $paymentService->addAdvanceCreditAndApplyToInvoices($customer, $data, $invoiceAllocations);
                } else {
                    $paymentService->addAdvanceCredit($customer, $data);
                }
            } catch (InvalidArgumentException $exception) {
                return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
            }

            $preferenceService->remember($request->user(), $rememberAsDefault, $data['payment_method'], $data['payment_account_id']);

            return redirect()->route('customers.show', $customer)->with('success', 'Payment was saved as advance.');
        }

        if (! $invoice) {
            try {
                $paymentService->addAdvanceCredit($customer, $data);
                $renewedMonths = $advanceRenewalService->renew(
                    $customer,
                    $data['payment_date'],
                    24,
                    'Automatic renewal from advance balance for remembered package.',
                );
            } catch (InvalidArgumentException $exception) {
                return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
            }

            $preferenceService->remember($request->user(), $rememberAsDefault, $data['payment_method'], $data['payment_account_id']);

            return redirect()->route('customers.show', $customer)->with('success', $renewedMonths > 0
                ? "Payment saved and {$renewedMonths} future month(s) was renewed on MikroTik."
                : 'No due invoice found. Payment was added to party advance balance.');
        }

        try {
            $paymentService->recordPayment($invoice, $data);
            $renewedMonths = $advanceRenewalService->renew(
                $customer,
                $data['payment_date'],
                24,
                'Automatic renewal from advance balance for remembered package.',
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        $preferenceService->remember($request->user(), $rememberAsDefault, $data['payment_method'], $data['payment_account_id']);

        return redirect()->route('customers.show', $customer)->with('success', $renewedMonths > 0
            ? "Party payment recorded successfully and {$renewedMonths} future month(s) was renewed on MikroTik."
            : 'Party payment recorded successfully.');
    }

    public function createAdvance(Customer $customer)
    {
        return redirect()->route('customers.payments.create', $customer);
    }

    public function storeAdvance(Request $request, Customer $customer, PaymentService $paymentService, PaymentAccountPreferenceService $preferenceService)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:cash,bkash,nagad,bank'],
            'payment_account_id' => ['nullable', 'exists:payment_accounts,id'],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string'],
            'invoice_allocations' => ['nullable', 'array'],
            'invoice_allocations.*' => ['nullable', 'numeric', 'min:0'],
            'set_as_default' => ['nullable', 'boolean'],
        ]);
        $rememberAsDefault = $request->boolean('set_as_default');
        unset($data['set_as_default']);

        $this->logSubmittedAmount($request, $customer, $data, 'customer_advance');

        if ($data['payment_method'] === 'cash') {
            $data['payment_account_id'] = null;
        } elseif (empty($data['payment_account_id'])) {
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
            $invoiceAllocations = collect($data['invoice_allocations'] ?? [])
                ->filter(fn ($amount) => (float) $amount > 0)
                ->all();

            if ($invoiceAllocations) {
                $paymentService->addAdvanceCreditAndApplyToInvoices($customer, $data, $invoiceAllocations);
            } else {
                $paymentService->addAdvanceCredit($customer, $data);
            }
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        $preferenceService->remember($request->user(), $rememberAsDefault, $data['payment_method'], $data['payment_account_id']);

        return redirect()->route('customers.payments.create', $customer)->with('success', 'Advance payment saved successfully.');
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

    public function renewFromAdvance(Customer $customer, AdvanceRenewalService $advanceRenewalService)
    {
        try {
            $renewedMonths = $advanceRenewalService->renew(
                $customer,
                now()->toDateString(),
                1,
                'Manual one-month renewal from customer advance balance.',
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['advance_renewal' => $exception->getMessage()]);
        }

        if ($renewedMonths <= 0) {
            return back()->withErrors([
                'advance_renewal' => 'Renewal could not be completed. Check that the party has a package, no unpaid invoice, and enough advance balance for one full month.',
            ]);
        }

        return redirect()->route('customers.show', $customer)
            ->with('success', 'One month validity renewed from advance balance.');
    }

    private function logSubmittedAmount(Request $request, Customer $customer, array $data, string $source): void
    {
        Log::info('Payment amount submission audit.', [
            'source' => $source,
            'customer_id' => $customer->id,
            'user_id' => $request->user()?->id,
            'raw_amount' => (string) $request->input('amount'),
            'validated_amount' => (string) ($data['amount'] ?? ''),
        ]);
    }
}
