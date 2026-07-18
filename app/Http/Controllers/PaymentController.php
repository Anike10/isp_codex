<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\CustomerBalanceTransaction;
use App\Models\Payment;
use App\Models\PaymentAccount;
use App\Services\PaymentService;
use App\Services\PrintContextService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        return view('payments.index', [
            'payments' => Payment::with(['customer', 'invoice', 'account', 'entryByUser'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = trim((string) $request->query('search'));
                    $query->where(function ($query) use ($search) {
                        $query->where('note', 'like', "%{$search}%")
                            ->orWhereHas('customer', fn ($query) => $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('connection_id', 'like', "%{$search}%"))
                            ->orWhereHas('invoice', fn ($query) => $query
                                ->where('invoice_no', 'like', "%{$search}%")
                                ->orWhere('billing_month', 'like', "%{$search}%"))
                            ->orWhereHas('account', fn ($query) => $query
                                ->where('account_name', 'like', "%{$search}%")
                                ->orWhere('account_number', 'like', "%{$search}%"));
                    });
                })
                ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', $request->query('payment_method')))
                ->when($request->filled('payment_account_id'), fn ($query) => $query->where('payment_account_id', $request->integer('payment_account_id')))
                ->when($request->filled('from'), fn ($query) => $query->whereDate('payment_date', '>=', $request->date('from')))
                ->when($request->filled('to'), fn ($query) => $query->whereDate('payment_date', '<=', $request->date('to')))
                ->when($request->filled('min_amount'), fn ($query) => $query->where('amount', '>=', (float) $request->query('min_amount')))
                ->when($request->filled('max_amount'), fn ($query) => $query->where('amount', '<=', (float) $request->query('max_amount')))
                ->latest()
                ->paginate($this->perPage($request))
                ->appends($request->query()),
            'advanceCredits' => CustomerBalanceTransaction::with(['customer', 'account', 'entryByUser'])
                ->where('direction', 'credit')
                ->whereNull('payment_id')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = trim((string) $request->query('search'));
                    $query->where(function ($query) use ($search) {
                        $query->where('note', 'like', "%{$search}%")
                            ->orWhere('reference', 'like', "%{$search}%")
                            ->orWhereHas('customer', fn ($query) => $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('connection_id', 'like', "%{$search}%"))
                            ->orWhereHas('account', fn ($query) => $query
                                ->where('account_name', 'like', "%{$search}%")
                                ->orWhere('account_number', 'like', "%{$search}%"));
                    });
                })
                ->when($request->filled('payment_method'), fn ($query) => $query->where('payment_method', $request->query('payment_method')))
                ->when($request->filled('payment_account_id'), fn ($query) => $query->where('payment_account_id', $request->integer('payment_account_id')))
                ->when($request->filled('from'), fn ($query) => $query->whereDate('transaction_date', '>=', $request->date('from')))
                ->when($request->filled('to'), fn ($query) => $query->whereDate('transaction_date', '<=', $request->date('to')))
                ->when($request->filled('min_amount'), fn ($query) => $query->where('amount', '>=', (float) $request->query('min_amount')))
                ->when($request->filled('max_amount'), fn ($query) => $query->where('amount', '<=', (float) $request->query('max_amount')))
                ->latest()
                ->paginate($this->perPage($request), ['*'], 'advance_page')
                ->appends($request->query()),
            'paymentAccounts' => PaymentAccount::where('status', 'active')->orderBy('payment_method')->orderBy('account_name')->get(),
        ]);
    }

    public function create()
    {
        return view('payments.create', [
            'invoices' => Invoice::with('customer')->where('due_amount', '>', 0)->latest()->get(),
            'paymentAccounts' => PaymentAccount::where('status', 'active')->orderBy('payment_method')->orderBy('account_name')->get(),
        ]);
    }

    public function show(Payment $payment)
    {
        $payment->load(['customer', 'invoice', 'account', 'allocations.invoice', 'balanceTransactions', 'entryByUser']);
        $versions = $payment->versions()->paginate(10, ['*'], 'history_page')->withQueryString();

        return view('payments.show', compact('payment', 'versions'));
    }

    public function voucher(Request $request, Payment $payment, PrintContextService $printContext)
    {
        return view('accounting.voucher', array_merge([
            'voucher' => $this->paymentVoucherData($payment),
            'printable' => $payment, 'documentType' => 'payment_voucher',
        ], $printContext->for($request)));
    }

    public function thermalVoucher(Request $request, Payment $payment, PrintContextService $printContext)
    {
        return view('accounting.thermal_voucher', array_merge([
            'voucher' => $this->paymentVoucherData($payment),
            'printable' => $payment, 'documentType' => 'payment_thermal_voucher',
        ], $printContext->for($request)));
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

        $invoice = Invoice::findOrFail($data['invoice_id']);

        try {
            $paymentService->recordPayment($invoice, $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        if ($request->input('redirect_to') === 'invoice') {
            return redirect()->route('invoices.show', $invoice)->with('success', 'Payment recorded successfully.');
        }

        return redirect()->route('payments.index')->with('success', 'Payment recorded successfully.');
    }

    private function paymentVoucherData(Payment $payment): array
    {
        $payment->loadMissing(['customer', 'invoice', 'account', 'allocations.invoice']);
        $canOpenInvoices = request()->user()?->hasPermission('manage_invoices');

        $allocations = $payment->allocations
            ->sortBy([
                ['invoice.due_date', 'asc'],
                ['invoice.id', 'asc'],
            ])
            ->map(fn ($allocation): array => [
                'invoice_no' => $allocation->invoice?->invoice_no ?? 'N/A',
                'bill_month' => $allocation->invoice?->formatted_billing_month ?? '',
                'amount' => (float) $allocation->amount,
                'url' => $canOpenInvoices && $allocation->invoice ? route('invoices.show', $allocation->invoice) : null,
            ])
            ->values();

        $secondaryValue = $payment->invoice?->invoice_no ?? 'N/A';
        $billMonth = $payment->invoice?->formatted_billing_month ?? '';

        if ($allocations->count() > 1) {
            $secondaryValue = $allocations->count().' invoices';
            $billMonth = $allocations
                ->pluck('bill_month')
                ->filter()
                ->unique()
                ->implode(', ');
        }

        return [
            'title' => 'Money Receipt',
            'voucher_no' => 'PAY-'.$payment->id,
            'date' => $payment->payment_date,
            'type' => 'Party Payment',
            'amount' => (float) $payment->amount,
            'paid_to_label' => 'Received From',
            'paid_to' => $payment->customer->name,
            'secondary_label' => 'Invoice',
            'secondary_value' => $secondaryValue,
            'bill_month' => $billMonth,
            'method' => ucfirst($payment->payment_method),
            'account' => $payment->account ? $payment->account->account_name.' - '.$payment->account->account_number : 'Cash',
            'reference' => 'Payment #'.$payment->id,
            'note' => $payment->note ?: 'Party payment received.',
            'allocations' => $allocations->all(),
            'back_url' => route('payments.show', $payment),
        ];
    }
}
