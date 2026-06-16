<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\AppSetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentAccount;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Subscription;
use App\Services\BillingService;
use App\Services\InventoryService;
use App\Support\SerialNumberParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class InvoiceController extends Controller
{
    private const PAYMENT_NOTE_SETTING_KEY = 'invoice_payment_note';
    private const DEFAULT_PAYMENT_NOTE = 'Please pay the due amount by the due date. Keep this bill for your records.';

    public function index(Request $request)
    {
        $generationPreviewMonth = $request->filled('billing_month')
            ? $request->input('billing_month')
            : now()->format('Y-m');
        $generatePreviewCount = Subscription::query()
            ->where('status', 'active')
            ->whereHas('customer', fn ($query) => $query->where('status', 'active')->where('never_suspend', true))
            ->whereDoesntHave('customer.invoices', function ($query) use ($generationPreviewMonth) {
                $query->where('billing_month', $generationPreviewMonth)
                    ->where('invoice_type', 'service');
            })
            ->count();

        $invoiceQuery = Invoice::query()
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
            ->when($request->boolean('due_only'), fn ($query) => $query->where('due_amount', '>', 0))
            ->when($request->filled('min_due'), fn ($query) => $query->where('due_amount', '>=', (float) $request->input('min_due')))
            ->when($request->due_from, fn ($query, string $date) => $query->whereDate('due_date', '>=', $date))
            ->when($request->due_to, fn ($query, string $date) => $query->whereDate('due_date', '<=', $date));

        $invoiceSummary = [
            'total_count' => (clone $invoiceQuery)->count(),
            'unpaid_count' => (clone $invoiceQuery)->where('status', 'unpaid')->count(),
            'partial_count' => (clone $invoiceQuery)->where('status', 'partial')->count(),
            'paid_count' => (clone $invoiceQuery)->where('status', 'paid')->count(),
            'draft_count' => (clone $invoiceQuery)->whereNull('finalized_at')->count(),
            'final_count' => (clone $invoiceQuery)->whereNotNull('finalized_at')->count(),
            'total_amount' => (float) (clone $invoiceQuery)->sum('total'),
            'due_amount' => (float) (clone $invoiceQuery)->sum('due_amount'),
            'advance_balance' => (float) Customer::where('account_balance', '>', 0)->sum('account_balance'),
        ];

        $invoices = $invoiceQuery
            ->latest()
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return view('invoices.index', compact('invoices', 'invoiceSummary', 'generatePreviewCount', 'generationPreviewMonth'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        return view('invoices.create', [
            'customers' => $customers,
            'productSuggestionData' => $this->productSuggestionData(),
            'defaultPaymentNote' => $this->defaultPaymentNote(),
        ]);
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
            ->get(['id', 'name', 'phone', 'connection_id', 'is_customer', 'is_vendor'])
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'connection_id' => $customer->connection_id,
                'party_type' => collect([
                    $customer->is_customer ? 'Customer' : null,
                    $customer->is_vendor ? 'Vendor' : null,
                ])->filter()->implode(' + ') ?: 'Party',
            ]);
    }

    public function store(Request $request, InventoryService $inventoryService)
    {
        $data = $this->validateInvoiceData($request);

        [$customerId, $itemsData, $subtotal, $total, $data] = $this->prepareInvoiceData($data);

        try {
            $invoice = DB::transaction(function () use ($data, $customerId, $itemsData, $subtotal, $total, $inventoryService): Invoice {
                $invoice = Invoice::create([
                    'customer_id' => $customerId,
                    'invoice_no' => Invoice::generateInvoiceNo($customerId, $data['billing_month']),
                    'billing_month' => $data['billing_month'],
                    'invoice_type' => $data['invoice_type'] ?? 'product',
                    'subtotal' => $subtotal,
                    'discount' => $data['discount_amount'],
                    'discount_type' => $data['discount_type'],
                    'discount_value' => $data['discount'],
                    'vat' => $data['vat_amount'],
                    'vat_type' => $data['vat_type'],
                    'vat_value' => $data['vat'],
                    'total' => $total,
                    'paid_amount' => 0,
                    'due_amount' => max(0, $total),
                    'status' => $total <= 0 ? 'paid' : 'unpaid',
                    'due_date' => $data['due_date'] ?? null,
                    'payment_note' => $data['payment_note'] ?? null,
                    'public_note' => $data['public_note'] ?? null,
                    'show_public_note' => (bool) ($data['show_public_note'] ?? false),
                    'private_note' => $data['private_note'] ?? null,
                ]);

                foreach ($itemsData as $itemData) {
                    $invoiceItem = $invoice->items()->create($itemData);
                    $this->applyInvoiceItemInventory($invoice, $invoiceItem, $inventoryService);
                }

                return $invoice;
            });
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['items' => $exception->getMessage()]);
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

        return view('invoices.create', [
            'customers' => $customers,
            'invoice' => $invoice,
            'productSuggestionData' => $this->productSuggestionData($invoice),
            'defaultPaymentNote' => $this->defaultPaymentNote(),
        ]);
    }

    public function update(Request $request, Invoice $invoice, InventoryService $inventoryService)
    {
        if ($invoice->isFinalized()) {
            return redirect()->route('invoices.show', $invoice)->withErrors([
                'invoice' => 'Finalized invoices cannot be edited.',
            ]);
        }

        $data = $this->validateInvoiceData($request);
        [$customerId, $itemsData, $subtotal, $total, $data] = $this->prepareInvoiceData($data);

        try {
            DB::transaction(function () use ($invoice, $data, $customerId, $itemsData, $subtotal, $total, $inventoryService) {
                $this->restoreInvoiceInventory($invoice, $inventoryService);

                $paidAmount = (float) $invoice->paid_amount;
                $dueAmount = max(0, $total - $paidAmount);

                $invoice->update([
                    'customer_id' => $customerId,
                    'billing_month' => $data['billing_month'],
                    'invoice_type' => $data['invoice_type'] ?? $invoice->invoice_type ?? 'product',
                    'subtotal' => $subtotal,
                    'discount' => $data['discount_amount'],
                    'discount_type' => $data['discount_type'],
                    'discount_value' => $data['discount'],
                    'vat' => $data['vat_amount'],
                    'vat_type' => $data['vat_type'],
                    'vat_value' => $data['vat'],
                    'total' => $total,
                    'due_amount' => $dueAmount,
                    'status' => $dueAmount <= 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid'),
                    'due_date' => $data['due_date'] ?? null,
                    'payment_note' => $data['payment_note'] ?? null,
                    'public_note' => $data['public_note'] ?? null,
                    'show_public_note' => (bool) ($data['show_public_note'] ?? false),
                    'private_note' => $data['private_note'] ?? null,
                ]);

                $invoice->items()->delete();
                foreach ($itemsData as $itemData) {
                    $invoiceItem = $invoice->items()->create($itemData);
                    $this->applyInvoiceItemInventory($invoice, $invoiceItem, $inventoryService);
                }
            });
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['items' => $exception->getMessage()]);
        }

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
                'discount_type' => $invoice->discount_type ?? 'amount',
                'discount_value' => $invoice->discount_value ?? $invoice->discount,
                'vat' => $invoice->vat,
                'vat_type' => $invoice->vat_type ?? 'amount',
                'vat_value' => $invoice->vat_value ?? $invoice->vat,
                'total' => $invoice->total,
                'paid_amount' => 0,
                'due_amount' => $invoice->total,
                'status' => ((float) $invoice->total) <= 0 ? 'paid' : 'unpaid',
                'due_date' => $invoice->due_date?->copy()->addMonthNoOverflow(),
                'payment_note' => $invoice->payment_note,
                'public_note' => $invoice->public_note,
                'show_public_note' => $invoice->show_public_note,
                'private_note' => $invoice->private_note,
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
            'invoice_type' => ['nullable', 'in:service,product'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.serial_numbers' => ['nullable', 'string'],
            'discount_type' => ['required', 'in:amount,percent'],
            'discount' => ['required', 'numeric', 'min:0'],
            'vat_type' => ['required', 'in:amount,percent'],
            'vat' => ['required', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
            'payment_note' => ['nullable', 'string', 'max:5000'],
            'public_note' => ['nullable', 'string', 'max:5000'],
            'show_public_note' => ['nullable', 'boolean'],
            'private_note' => ['nullable', 'string', 'max:5000'],
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
                    'is_customer' => true,
                    'is_vendor' => false,
                ]);
            }

            $customerId = $customer->id;
        }

        $subtotal = 0;
        $itemsData = [];

        foreach ($data['items'] as $item) {
            $serialNumbers = app(SerialNumberParser::class)->parse($item['serial_numbers'] ?? '');
            $quantity = max((int) $item['quantity'], count($serialNumbers));
            $product = ! empty($item['product_id']) ? Product::find($item['product_id']) : null;
            $productType = $product?->product_type ?? null;
            $serviceGuaranteeDays = $product?->service_guarantee_days;
            $total = $quantity * $item['unit_price'];
            $subtotal += $total;
            $itemsData[] = [
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product_name'],
                'product_type' => $productType,
                'quantity' => $quantity,
                'unit_price' => $item['unit_price'],
                'total' => $total,
                'serial_numbers' => trim((string) ($item['serial_numbers'] ?? '')) ?: null,
                'warranty_days' => $product?->warranty_days,
                'service_guarantee_days' => $serviceGuaranteeDays,
                'service_guarantee_until' => $serviceGuaranteeDays ? now()->addDays($serviceGuaranteeDays)->toDateString() : null,
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

    private function productSuggestionData(?Invoice $invoice = null)
    {
        $invoiceSerials = $invoice
            ? $invoice->items->pluck('serial_numbers')->filter()->flatMap(fn (string $serials): array => app(SerialNumberParser::class)->parse($serials))->values()
            : collect();

        return Product::query()
            ->with(['serials' => function ($query) use ($invoiceSerials) {
                $query->where(function ($query) use ($invoiceSerials) {
                    $query->where('status', 'in_stock');

                    if ($invoiceSerials->isNotEmpty()) {
                        $query->orWhereIn('serial_number', $invoiceSerials);
                    }
                })->orderBy('serial_number');
            }])
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'brand' => $product->brand,
                'sale_price' => (float) $product->sale_price,
                'stock_quantity' => (int) $product->stock_quantity,
                'product_type' => $product->product_type,
                'service_guarantee_days' => $product->service_guarantee_days,
                'track_inventory' => (bool) $product->track_inventory,
                'track_serials' => (bool) $product->track_serial_numbers,
                'serials' => $product->serials->map(fn (ProductSerial $serial): array => [
                    'serial_number' => $serial->serial_number,
                    'warranty_until' => $serial->warranty_until?->format('Y-m-d'),
                    'status' => $serial->status,
                ])->values(),
            ])
            ->values();
    }

    private function applyInvoiceItemInventory(Invoice $invoice, InvoiceItem $invoiceItem, InventoryService $inventoryService): void
    {
        if (empty($invoiceItem->product_id)) {
            if (! empty($invoiceItem->serial_numbers)) {
                throw new InvalidArgumentException('Select a product before using serial numbers.');
            }

            return;
        }

        $product = Product::lockForUpdate()->findOrFail($invoiceItem->product_id);
        $quantity = (int) $invoiceItem->quantity;
        $serialNumbers = app(SerialNumberParser::class)->parse($invoiceItem->serial_numbers ?? '');

        if ($serialNumbers !== [] && ! $product->track_serial_numbers) {
            throw new InvalidArgumentException('Serial numbers can only be used for serial-tracked products.');
        }

        if ($product->track_inventory) {
            $inventoryService->moveStock($product, 'out', $quantity, 'Invoice '.$invoice->invoice_no, $invoice->invoice_no);
        }

        if ($serialNumbers === []) {
            return;
        }

        $serialRows = ProductSerial::query()
            ->where('product_id', $product->id)
            ->whereIn('serial_number', $serialNumbers)
            ->lockForUpdate()
            ->get()
            ->keyBy('serial_number');

        foreach ($serialNumbers as $serialNumber) {
            $serial = $serialRows->get($serialNumber);

            if (! $serial || $serial->status !== 'in_stock') {
                throw new InvalidArgumentException('Serial '.$serialNumber.' is not available for sale.');
            }

            $serial->update([
                'status' => 'sold',
                'customer_id' => $invoice->customer_id,
                'invoice_id' => $invoice->id,
                'invoice_item_id' => $invoiceItem->id,
                'sold_at' => now(),
                'note' => 'Sold via invoice '.$invoice->invoice_no,
            ]);
        }
    }

    private function restoreInvoiceInventory(Invoice $invoice, InventoryService $inventoryService): void
    {
        $invoice->loadMissing('items');

        foreach ($invoice->items as $item) {
            if (! $item->product_id) {
                continue;
            }

            $product = Product::lockForUpdate()->find($item->product_id);

            if (! $product) {
                continue;
            }

            if ($product->track_inventory) {
                $inventoryService->moveStock($product, 'in', (int) $item->quantity, 'Invoice edit restore '.$invoice->invoice_no, $invoice->invoice_no);
            }

            $serialNumbers = app(SerialNumberParser::class)->parse($item->serial_numbers ?? '');

            if ($serialNumbers === []) {
                continue;
            }

            ProductSerial::query()
                ->where('product_id', $product->id)
                ->whereIn('serial_number', $serialNumbers)
                ->where('status', 'sold')
                ->lockForUpdate()
                ->get()
                ->each(fn (ProductSerial $serial) => $serial->update([
                    'status' => 'in_stock',
                    'customer_id' => null,
                    'invoice_id' => null,
                    'invoice_item_id' => null,
                    'sold_at' => null,
                    'note' => 'Restored from draft invoice edit '.$invoice->invoice_no,
                ]));
        }
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
        $paymentNote = $this->paymentNoteForInvoice($invoice);

        return view('invoices.challan', compact('invoice', 'paymentNote'));
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

    public function editPaymentNoteDefault()
    {
        return view('invoices.payment_note_default', [
            'paymentNote' => $this->defaultPaymentNote(),
        ]);
    }

    public function updatePaymentNoteDefault(Request $request)
    {
        $data = $request->validate([
            'payment_note' => ['required', 'string', 'max:5000'],
        ]);

        AppSetting::setValue(self::PAYMENT_NOTE_SETTING_KEY, $data['payment_note']);

        return redirect()
            ->route('invoices.payment-note-default.edit')
            ->with('success', 'Default payment note updated successfully.');
    }

    private function defaultPaymentNote(): string
    {
        return AppSetting::value(self::PAYMENT_NOTE_SETTING_KEY, self::DEFAULT_PAYMENT_NOTE) ?: self::DEFAULT_PAYMENT_NOTE;
    }

    private function paymentNoteForInvoice(Invoice $invoice): string
    {
        $invoiceNote = trim((string) $invoice->payment_note);

        return $invoiceNote !== '' ? $invoiceNote : $this->defaultPaymentNote();
    }
}
