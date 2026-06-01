<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentAccount;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::query()
            ->with('customer')
            ->when($request->search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('invoice_no', 'like', "%{$search}%")
                        ->orWhere('billing_month', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('connection_id', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->status, fn ($query, string $status) => $query->where('status', $status))
            ->when($request->billing_month, fn ($query, string $month) => $query->where('billing_month', $month))
            ->when($request->invoice_type, fn ($query, string $type) => $query->where('invoice_type', $type))
            ->when($request->final_state === 'draft', fn ($query) => $query->whereNull('finalized_at'))
            ->when($request->final_state === 'final', fn ($query) => $query->whereNotNull('finalized_at'))
            ->latest()
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return view('invoices.index', compact('invoices'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        return view('invoices.create', compact('customers'));
    }

    public function searchCustomers(Request $request)
    {
        $search = trim((string) $request->query('q'));

        return Customer::query()
            ->where('status', 'active')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('connection_id', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'phone', 'connection_id']);
    }

    public function store(Request $request)
    {
        $data = $this->validateInvoiceData($request);

        [$customerId, $itemsData, $subtotal, $total, $data] = $this->prepareInvoiceData($data);

        $invoice = Invoice::create([
            'customer_id' => $customerId,
            'invoice_no' => Invoice::generateInvoiceNo($customerId, $data['billing_month']),
            'billing_month' => $data['billing_month'],
            'invoice_type' => 'product',
            'subtotal' => $subtotal,
            'discount' => $data['discount_amount'],
            'vat' => $data['vat_amount'],
            'total' => $total,
            'paid_amount' => 0,
            'due_amount' => max(0, $total),
            'status' => $total <= 0 ? 'paid' : 'unpaid',
            'due_date' => $data['due_date'] ?? null,
        ]);

        foreach ($itemsData as $itemData) {
            $invoice->items()->create($itemData);
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created as draft. You can edit it until finalizing.');
    }

    public function edit(Invoice $invoice)
    {
        if ($invoice->isFinalized()) {
            return redirect()->route('invoices.show', $invoice)->withErrors([
                'invoice' => 'Finalized invoices cannot be edited.',
            ]);
        }

        $invoice->load(['customer', 'items']);
        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        return view('invoices.create', compact('customers', 'invoice'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->isFinalized()) {
            return redirect()->route('invoices.show', $invoice)->withErrors([
                'invoice' => 'Finalized invoices cannot be edited.',
            ]);
        }

        $data = $this->validateInvoiceData($request);
        [$customerId, $itemsData, $subtotal, $total, $data] = $this->prepareInvoiceData($data);

        DB::transaction(function () use ($invoice, $data, $customerId, $itemsData, $subtotal, $total) {
            $paidAmount = (float) $invoice->paid_amount;
            $dueAmount = max(0, $total - $paidAmount);

            $invoice->update([
                'customer_id' => $customerId,
                'billing_month' => $data['billing_month'],
                'subtotal' => $subtotal,
                'discount' => $data['discount_amount'],
                'vat' => $data['vat_amount'],
                'total' => $total,
                'due_amount' => $dueAmount,
                'status' => $dueAmount <= 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid'),
                'due_date' => $data['due_date'] ?? null,
            ]);

            $invoice->items()->delete();
            foreach ($itemsData as $itemData) {
                $invoice->items()->create($itemData);
            }
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Draft invoice updated successfully.');
    }

    public function finalize(Invoice $invoice)
    {
        if (! $invoice->isFinalized()) {
            $invoice->update(['finalized_at' => now()]);
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice finalized. Editing is now locked.');
    }

    public function copyForNextMonth(Invoice $invoice)
    {
        $invoice->loadMissing('items');
        $nextBillingMonth = Carbon::createFromFormat('!Y-m', $invoice->billing_month)
            ->addMonthNoOverflow()
            ->format('Y-m');

        $newInvoice = DB::transaction(function () use ($invoice, $nextBillingMonth) {
            $copy = Invoice::create([
                'customer_id' => $invoice->customer_id,
                'invoice_no' => Invoice::generateInvoiceNo($invoice->customer_id, $nextBillingMonth),
                'billing_month' => $nextBillingMonth,
                'invoice_type' => $invoice->invoice_type,
                'subtotal' => $invoice->subtotal,
                'discount' => $invoice->discount,
                'vat' => $invoice->vat,
                'total' => $invoice->total,
                'paid_amount' => 0,
                'due_amount' => $invoice->total,
                'status' => ((float) $invoice->total) <= 0 ? 'paid' : 'unpaid',
                'due_date' => $invoice->due_date?->copy()->addMonthNoOverflow(),
            ]);

            foreach ($invoice->items as $item) {
                $copy->items()->create([
                    'product_name' => $item->product_name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total,
                ]);
            }

            return $copy;
        });

        return redirect()
            ->route('invoices.show', $newInvoice)
            ->with('success', 'Invoice copied for '.$newInvoice->formatted_billing_month.'.');
    }

    private function validateInvoiceData(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'customer_phone' => ['required_without:customer_id', 'nullable', 'string', 'max:30'],
            'billing_month' => ['required', 'date_format:Y-m'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['required', 'in:amount,percent'],
            'discount' => ['required', 'numeric', 'min:0'],
            'vat_type' => ['required', 'in:amount,percent'],
            'vat' => ['required', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
        ]);
    }

    private function prepareInvoiceData(array $data): array
    {
        $customerId = $data['customer_id'] ?? null;

        if (! $customerId) {
            $customer = Customer::where('phone', $data['customer_phone'])->first();

            if (! $customer) {
                $customer = Customer::create([
                    'name' => $data['customer_name'],
                    'phone' => $data['customer_phone'],
                    'connection_id' => $this->generateCustomerConnectionId(),
                    'address' => '',
                    'status' => 'active',
                ]);
            }

            $customerId = $customer->id;
        }

        $subtotal = 0;
        $itemsData = [];

        foreach ($data['items'] as $item) {
            $total = $item['quantity'] * $item['unit_price'];
            $subtotal += $total;
            $itemsData[] = [
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $total,
            ];
        }

        $discountAmount = $this->resolveAdjustmentAmount($subtotal, $data['discount'], $data['discount_type']);
        $afterDiscount = max(0, $subtotal - $discountAmount);
        $vatAmount = $this->resolveAdjustmentAmount($afterDiscount, $data['vat'], $data['vat_type']);
        $total = $afterDiscount + $vatAmount;

        $data['discount_amount'] = $discountAmount;
        $data['vat_amount'] = $vatAmount;

        return [$customerId, $itemsData, $subtotal, $total, $data];
    }

    private function resolveAdjustmentAmount(float $baseAmount, float|int|string $value, string $type): float
    {
        $value = (float) $value;

        if ($type === 'percent') {
            return round($baseAmount * $value / 100, 2);
        }

        return round($value, 2);
    }

    private function generateCustomerConnectionId(): string
    {
        do {
            $connectionId = 'AUTO-'.now()->format('YmdHis').'-'.random_int(100, 999);
        } while (Customer::where('connection_id', $connectionId)->exists());

        return $connectionId;
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'payments.account', 'allocations.payment.account', 'items']);

        $paymentAccounts = collect();

        if (auth()->user()?->hasPermission('manage_payments')) {
            $paymentAccounts = PaymentAccount::where('status', 'active')
                ->orderBy('payment_method')
                ->orderBy('account_name')
                ->get();
        }

        return view('invoices.show', compact('invoice', 'paymentAccounts'));
    }

    public function challan(Invoice $invoice)
    {
        $invoice->load(['customer', 'items']);

        return view('invoices.challan', compact('invoice'));
    }

    public function quotation(Invoice $invoice)
    {
        $invoice->load(['customer', 'items']);

        return view('invoices.quotation', compact('invoice'));
    }

    public function deliveryChallan(Invoice $invoice)
    {
        $invoice->load(['customer', 'items']);

        return view('invoices.delivery_challan', compact('invoice'));
    }

    public function generate(Request $request, BillingService $billingService)
    {
        $data = $request->validate([
            'billing_month' => ['required', 'date_format:Y-m'],
        ]);

        $activeSubscriptions = Subscription::query()
            ->where('status', 'active')
            ->whereHas('customer', fn ($query) => $query->where('status', 'active')->where('never_suspend', true))
            ->count();

        if ($activeSubscriptions === 0) {
            return back()->withErrors([
                'billing_month' => 'No special never-suspend customer subscription found. Only special customers are auto-generated from this button.',
            ]);
        }

        $created = $billingService->generateMonthlyBills($data['billing_month']);

        if ($created->isEmpty()) {
            $formattedBillingMonth = Carbon::createFromFormat('!Y-m', $data['billing_month'])->format('F Y');

            return redirect()
                ->route('invoices.index', ['billing_month' => $data['billing_month']])
                ->with('success', 'Bills for '.$formattedBillingMonth.' are already generated.');
        }

        return redirect()
            ->route('invoices.index', ['billing_month' => $data['billing_month']])
            ->with('success', $created->count().' invoice(s) generated.');
    }
}
