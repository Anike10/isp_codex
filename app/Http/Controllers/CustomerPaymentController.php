<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentAccount;
use App\Services\BillingService;
use App\Services\MikrotikCustomerSyncService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Throwable;

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

        $this->logSubmittedAmount($request, $customer, $data, 'customer_payment');

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

        if (! $invoice) {
            try {
                $paymentService->addAdvanceCredit($customer, $data);
                $renewedMonths = $this->renewExpiredPartyFromAdvance($customer, $billingService, $paymentService, $data);
            } catch (InvalidArgumentException $exception) {
                return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
            }

            return redirect()->route('customers.show', $customer)->with('success', $renewedMonths > 0
                ? "Payment saved and {$renewedMonths} future month(s) was renewed on MikroTik."
                : 'No due invoice found. Payment was added to party advance balance.');
        }

        try {
            $paymentService->recordPayment($invoice, $data);
            $renewedMonths = $this->renewExpiredPartyFromAdvance($customer, $billingService, $paymentService, $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()->route('customers.show', $customer)->with('success', $renewedMonths > 0
            ? "Party payment recorded successfully and {$renewedMonths} future month(s) was renewed on MikroTik."
            : 'Party payment recorded successfully.');
    }

    public function createAdvance(Customer $customer)
    {
        return redirect()->route('customers.payments.create', $customer);
    }

    public function storeAdvance(Request $request, Customer $customer, PaymentService $paymentService, BillingService $billingService)
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
        ]);

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
            $dueBeforeAdvance = (float) $customer->invoices()->where('due_amount', '>', 0)->sum('due_amount');
            $invoiceAllocations = collect($data['invoice_allocations'] ?? [])
                ->filter(fn ($amount) => (float) $amount > 0)
                ->all();
            $renewedMonths = 0;

            if ($invoiceAllocations) {
                $paymentService->addAdvanceCreditAndApplyToInvoices($customer, $data, $invoiceAllocations);
            } else {
                $paymentService->addAdvanceCredit($customer, $data);

                // An expired Party may pay into advance first. If there were no
                // old unpaid bills and the saved balance covers its remembered
                // package, use that balance for a new monthly renewal at once.
                $renewedMonths = $dueBeforeAdvance <= 0
                    ? $this->renewExpiredPartyFromAdvance($customer, $billingService, $paymentService, $data)
                    : 0;
            }
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()->route('customers.payments.create', $customer)->with('success', $renewedMonths > 0
            ? "Advance payment saved and {$renewedMonths} future month(s) was renewed on MikroTik."
            : 'Advance payment saved successfully.');
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

    private function renewExpiredPartyFromAdvance(Customer $customer, BillingService $billingService, PaymentService $paymentService, array $data): int
    {
        $renewedMonths = 0;
        $maxRenewals = 24;
        $paymentDate = $data['payment_date'];

        for ($step = 0; $step < $maxRenewals; $step++) {
            $customer->refresh();

            $dueExists = (float) $customer->invoices()->where('due_amount', '>', 0)->sum('due_amount') > 0;
            if ($dueExists) {
                break;
            }

            $renewalInvoice = $billingService->generateNextRenewalServiceBillForCustomer($customer, $paymentDate);
            if (! $renewalInvoice || (float) $renewalInvoice->due_amount <= 0) {
                break;
            }

            if ((float) $customer->refresh()->account_balance < (float) $renewalInvoice->due_amount) {
                break;
            }

            $paymentService->applyAdvanceToInvoice($customer->refresh(), $renewalInvoice, [
                'amount' => $renewalInvoice->due_amount,
                'payment_date' => $paymentDate,
                'note' => 'Automatic renewal from advance balance for remembered package.',
            ], true);

            $renewedMonths++;
        }

        if ($renewedMonths <= 0) {
            return 0;
        }

        $customer = $customer->refresh();
        $paymentService->extendPaidServiceValidityFromCurrent(
            $customer,
            $paymentDate,
            $renewedMonths,
            'Automatic renewal from advance balance for remembered package.'
        );

        $subscription = $customer->activeSubscription ?: $customer->subscriptions()->latest()->first();
        if ($subscription) {
            $subscription->update([
                'status' => 'active',
                'end_date' => null,
            ]);
        }

        try {
            app(MikrotikCustomerSyncService::class)->sync($customer->refresh());
        } catch (Throwable $exception) {
            Log::warning('MikroTik sync failed after automatic advance renewal.', [
                'customer_id' => $customer->id,
                'renewed_months' => $renewedMonths,
                'error' => $exception->getMessage(),
            ]);
        }

        return $renewedMonths;
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
