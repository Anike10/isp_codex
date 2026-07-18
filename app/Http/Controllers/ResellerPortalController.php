<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerBalanceTransaction;
use App\Models\Invoice;
use App\Models\PaymentAccount;
use App\Services\BillingService;
use App\Services\PaymentService;
use App\Services\PrintContextService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ResellerPortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $reseller = $request->user()?->reseller;

        abort_unless($reseller?->is_reseller, 403, 'This login is not linked to an active reseller account.');

        return $this->dashboardFor($reseller, false);
    }

    public function dashboardFor(Customer $reseller, bool $isAdminView)
    {
        $reseller->loadCount('resellerCustomers');
        $reseller->load(['commissionHistories.changedByUser']);
        $customers = $reseller->resellerCustomers()
            ->with(['activeSubscription.package', 'latestInvoice'])
            ->withSum('invoices as total_due_amount', 'due_amount')
            ->orderBy('name')
            ->get();
        $dueInvoices = Invoice::query()
            ->whereIn('customer_id', $customers->pluck('id'))
            ->where('due_amount', '>', 0)
            ->with('customer')
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();
        $transactions = $reseller->balanceTransactions()
            ->with('invoice.customer')
            ->latest('transaction_date')
            ->latest('id')
            ->limit(50)
            ->get();
        $spentToday = $this->spentOn($reseller, now()->toDateString());
        $dailyLimit = $reseller->reseller_daily_payment_limit !== null
            ? (float) $reseller->reseller_daily_payment_limit
            : null;
        $dailyRemaining = $dailyLimit === null ? null : max(0, $dailyLimit - $spentToday);

        return view('resellers.dashboard', compact(
            'reseller',
            'customers',
            'dueInvoices',
            'transactions',
            'spentToday',
            'dailyLimit',
            'dailyRemaining',
            'isAdminView'
        ));
    }

    public function pay(Request $request, Invoice $invoice, PaymentService $paymentService)
    {
        $reseller = $request->user()?->reseller;
        abort_unless($reseller?->is_reseller, 403, 'This login is not linked to a reseller account.');

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'operation_key' => ['required', 'uuid'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['payment_date'] = now()->toDateString();
        $data['entry_by'] = $request->user()->id;

        try {
            $paymentService->applyResellerWalletToInvoice($reseller, $invoice, $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()->route('reseller.dashboard')->with('success', 'Invoice payment completed from reseller wallet.');
    }

    public function generateInvoice(Request $request, Customer $customer, BillingService $billingService)
    {
        $reseller = $this->authorizedResellerCustomer($request, $customer);
        $invoice = $billingService->generateCurrentServiceBillForCustomer($customer);

        if (! $invoice) {
            return back()->withErrors(['invoice' => 'This party has no package for service invoice generation.']);
        }

        $message = $invoice->wasRecentlyCreated
            ? 'Service invoice created with '.$reseller->reseller_commission_percent.'% reseller commission discount.'
            : 'The current service invoice already exists.';

        return redirect()->route('reseller.dashboard')->with('success', $message);
    }

    public function createPayment(Request $request, Customer $customer)
    {
        $reseller = $this->authorizedResellerCustomer($request, $customer);
        $customer->load('activeSubscription.package');
        $dueTotal = round((float) $customer->invoices()->where('due_amount', '>', 0)->sum('due_amount'), 2);
        $grossPackagePrice = (float) ($customer->activeSubscription?->package?->monthly_price ?? 0);
        $commissionAmount = round($grossPackagePrice * (float) $reseller->reseller_commission_percent / 100, 2);
        $suggestedAmount = $dueTotal > 0 ? $dueTotal : max(0, $grossPackagePrice - $commissionAmount);

        return view('resellers.customer_payment', [
            'reseller' => $reseller,
            'customer' => $customer,
            'dueTotal' => $dueTotal,
            'grossPackagePrice' => $grossPackagePrice,
            'commissionAmount' => $commissionAmount,
            'suggestedAmount' => $suggestedAmount,
            'paymentAccounts' => PaymentAccount::where('status', 'active')->orderBy('payment_method')->orderBy('account_name')->get(),
        ]);
    }

    public function storePayment(Request $request, Customer $customer, BillingService $billingService, PaymentService $paymentService)
    {
        $this->authorizedResellerCustomer($request, $customer);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['required', 'in:cash,bkash,nagad,bank'],
            'payment_account_id' => ['nullable', 'exists:payment_accounts,id'],
            'payment_date' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['entry_by'] = $request->user()->id;

        if ($data['payment_method'] === 'cash') {
            $data['payment_account_id'] = null;
        } else {
            $account = PaymentAccount::query()
                ->whereKey($data['payment_account_id'] ?? null)
                ->where('payment_method', $data['payment_method'])
                ->where('status', 'active')
                ->first();

            if (! $account) {
                return back()->withInput()->withErrors(['payment_account_id' => 'Select a valid account for the payment method.']);
            }
        }

        $billingService->generateCurrentServiceBillForCustomer($customer);
        $invoice = $customer->invoices()->where('due_amount', '>', 0)->orderBy('due_date')->orderBy('id')->first();

        try {
            if ($invoice) {
                $paymentService->recordPayment($invoice, $data);
            } else {
                $paymentService->addAdvanceCredit($customer, $data);
            }
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()->route('reseller.dashboard')->with('success', 'Party payment recorded; invoice commission snapshot was preserved.');
    }

    public function printInvoice(Request $request, Invoice $invoice, PrintContextService $printContext)
    {
        $invoice->loadMissing('customer');
        $this->authorizedResellerCustomer($request, $invoice->customer);

        return app(InvoiceController::class)->challan($request, $invoice, $printContext);
    }

    private function authorizedResellerCustomer(Request $request, Customer $customer): Customer
    {
        $reseller = $request->user()?->reseller;
        abort_unless($reseller?->is_reseller && (int) $customer->reseller_id === (int) $reseller->id, 403);

        return $reseller;
    }

    private function spentOn(Customer $reseller, string $date): float
    {
        return (float) CustomerBalanceTransaction::query()
            ->where('customer_id', $reseller->id)
            ->where('payment_method', 'reseller_wallet')
            ->where('direction', 'debit')
            ->whereDate('transaction_date', $date)
            ->sum('amount');
    }
}
