<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentAccount;
use App\Services\BillingService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
                $renewed = $this->renewExpiredPartyFromAdvance($customer, $billingService, $paymentService, $data);
            } catch (InvalidArgumentException $exception) {
                return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
            }

            return redirect()->route('customers.show', $customer)->with('success', $renewed ? 'Payment saved and the remembered package was renewed on MikroTik.' : 'No due invoice found. Payment was added to party advance balance.');
        }

        try {
            $paymentService->recordPayment($invoice, $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()->route('customers.show', $customer)->with('success', 'Party payment recorded successfully.');
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

            if ($invoiceAllocations) {
                $paymentService->addAdvanceCreditAndApplyToInvoices($customer, $data, $invoiceAllocations);
            } else {
                $paymentService->addAdvanceCredit($customer, $data);

                // An expired Party may pay into advance first. If there were no
                // old unpaid bills and the saved balance covers its remembered
                // package, use that balance for a new monthly renewal at once.
                $renewed = $dueBeforeAdvance <= 0
                    && $this->renewExpiredPartyFromAdvance($customer, $billingService, $paymentService, $data);
            }
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()->route('customers.payments.create', $customer)->with('success', ($renewed ?? false) ? 'Advance saved and the remembered package was renewed on MikroTik.' : 'Advance payment saved successfully.');
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

    private function renewExpiredPartyFromAdvance(Customer $customer, BillingService $billingService, PaymentService $paymentService, array $data): bool
    {
        $customer->refresh();
        $validityExpired = $customer->service_valid_until?->copy()->endOfDay()->lt(now()) ?? false;
        $needsRenewal = $customer->status !== 'active' || $validityExpired || $customer->grace_used_at !== null;

        if (! $needsRenewal) {
            return false;
        }

        $renewalInvoice = $billingService->generateNextRenewalServiceBillForCustomer($customer, $data['payment_date']);

        if (! $renewalInvoice || (float) $renewalInvoice->due_amount <= 0 || (float) $customer->refresh()->account_balance < (float) $renewalInvoice->due_amount) {
            return false;
        }

        $paymentService->applyAdvanceToInvoice($customer, $renewalInvoice, [
            'amount' => $renewalInvoice->due_amount,
            'payment_date' => $data['payment_date'],
            'note' => 'Automatic renewal from advance balance for remembered package.',
        ]);

        return $customer->refresh()->status === 'active';
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
