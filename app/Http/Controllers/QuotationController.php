<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\Quotation;
use App\Observers\RecordVersionObserver;
use App\Services\InventoryService;
use App\Services\RecordVersionService;
use App\Services\PrintContextService;
use App\Support\SerialNumberParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $quotations = Quotation::query()
            ->with(['customer', 'convertedInvoice'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->query('search'));
                $query->where(function ($query) use ($search): void {
                    $query->where('quotation_no', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('quotation_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('quotation_date', '<=', $request->date('to')))
            ->when($request->filled('valid_until'), fn ($query) => $query->whereDate('valid_until', '<=', $request->date('valid_until')))
            ->latest()
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return view('quotations.index', compact('quotations'));
    }

    public function create()
    {
        return view('invoices.create', [
            'documentMode' => 'quotation',
            'customers' => Customer::where('status', 'active')->orderBy('name')->get(),
            'productSuggestionData' => $this->productSuggestionData(),
            'defaultPaymentNote' => 'This quotation is valid until the stated date. Delivery, installation, and payment terms are subject to final confirmation.',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        try {
            [$customerId, $items, $subtotal, $total, $data] = $this->prepareData($data);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['items' => $exception->getMessage()]);
        }

        $quotation = DB::transaction(function () use ($customerId, $items, $subtotal, $total, $data): Quotation {
            $quotation = Quotation::create($this->quotationAttributes($customerId, $subtotal, $total, $data) + [
                'quotation_no' => Quotation::generateQuotationNo(),
                'status' => 'draft',
            ]);
            foreach ($items as $item) {
                $quotation->items()->create($item);
            }

            return $quotation;
        });

        return redirect()->route('quotations.show', $quotation)->with('success', 'Quotation created successfully. It is not included in accounting.');
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['customer', 'items.product', 'convertedInvoice']);
        $versions = $quotation->versions()->paginate(10, ['*'], 'history_page')->withQueryString();

        return view('quotations.show', compact('quotation', 'versions'));
    }

    public function edit(Quotation $quotation)
    {
        if ($quotation->converted_invoice_id) {
            return redirect()->route('quotations.show', $quotation)->withErrors(['quotation' => 'Converted quotations cannot be edited.']);
        }

        $quotation->load(['customer', 'items']);

        return view('invoices.create', [
            'documentMode' => 'quotation',
            'quotation' => $quotation,
            'customers' => Customer::where('status', 'active')->orderBy('name')->get(),
            'productSuggestionData' => $this->productSuggestionData($quotation),
            'defaultPaymentNote' => 'This quotation is valid until the stated date. Delivery, installation, and payment terms are subject to final confirmation.',
        ]);
    }

    public function update(Request $request, Quotation $quotation, RecordVersionService $recordVersionService)
    {
        if ($quotation->converted_invoice_id) {
            return redirect()->route('quotations.show', $quotation)->withErrors(['quotation' => 'Converted quotations cannot be edited.']);
        }

        $data = $this->validateData($request);

        try {
            [$customerId, $items, $subtotal, $total, $data] = $this->prepareData($data);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['items' => $exception->getMessage()]);
        }

        $becameConverted = false;

        DB::transaction(function () use (&$quotation, $customerId, $items, $subtotal, $total, $data, $recordVersionService, &$becameConverted): void {
            $quotation = Quotation::query()->whereKey($quotation->id)->lockForUpdate()->firstOrFail();

            if ($quotation->converted_invoice_id) {
                $becameConverted = true;

                return;
            }

            $oldSnapshot = $recordVersionService->snapshot($quotation, ['customer', 'items']);
            RecordVersionObserver::withoutRecording(fn () => $quotation->update($this->quotationAttributes($customerId, $subtotal, $total, $data)));
            $quotation->items()->delete();
            foreach ($items as $item) {
                $quotation->items()->create($item);
            }

            $newSnapshot = $recordVersionService->snapshot($quotation->refresh(), ['customer', 'items']);
            $recordVersionService->recordUpdate($quotation, $oldSnapshot, $newSnapshot, [
                'source' => 'quotation_edit',
                'quotation_no' => $quotation->quotation_no,
            ]);
        });

        if ($becameConverted) {
            return redirect()->route('quotations.show', $quotation)->withErrors([
                'quotation' => 'Converted quotations cannot be edited.',
            ]);
        }

        return redirect()->route('quotations.show', $quotation)->with('success', 'Quotation updated successfully.');
    }

    public function print(Request $request, Quotation $quotation, PrintContextService $printContext)
    {
        $quotation->load(['customer', 'items']);

        return view('invoices.quotation', array_merge(['invoice' => $quotation], $printContext->for($request)));
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'customer_phone' => ['required_without:customer_id', 'nullable', 'string', 'max:30'],
            'quotation_date' => ['required', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:quotation_date'],
            'billing_month' => ['required', 'date_format:Y-m'],
            'invoice_type' => ['nullable', 'in:service,product'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.serial_numbers' => ['nullable', 'string'],
            'items.*.serialless_quantity' => ['nullable', 'integer', 'min:0'],
            'discount_type' => ['required', 'in:amount,percent'],
            'discount' => ['required', 'numeric', 'min:0'],
            'vat_type' => ['required', 'in:amount,percent'],
            'vat' => ['required', 'numeric', 'min:0'],
            'payment_note' => ['nullable', 'string', 'max:5000'],
            'public_note' => ['nullable', 'string', 'max:5000'],
            'show_public_note' => ['nullable', 'boolean'],
            'private_note' => ['nullable', 'string', 'max:5000'],
        ]);
    }

    private function prepareData(array $data): array
    {
        $customerId = $data['customer_id'] ?? null;
        if (! $customerId) {
            $customer = Customer::firstOrCreate(
                ['phone' => $data['customer_phone']],
                [
                    'name' => $data['customer_name'],
                    'connection_id' => 'AUTO-QT-'.now()->format('YmdHis').'-'.random_int(100, 999),
                    'address' => '', 'status' => 'active', 'is_customer' => true, 'is_vendor' => false,
                ],
            );
            $customerId = $customer->id;
        }

        $items = [];
        $subtotal = 0.0;
        foreach ($data['items'] as $item) {
            $product = ! empty($item['product_id']) ? Product::find($item['product_id']) : null;
            $serials = app(SerialNumberParser::class)->parse($item['serial_numbers'] ?? '');
            $serialless = (int) ($item['serialless_quantity'] ?? 0);
            $quantity = max((int) $item['quantity'], count($serials) + $serialless);

            if ($serials !== [] && ! $product?->track_serial_numbers) {
                throw new InvalidArgumentException('Serial numbers can only be used for serial-tracked quotation products.');
            }

            if (! $product?->track_serial_numbers) {
                $serialless = 0;
            } elseif (count($serials) + $serialless !== $quantity) {
                throw new InvalidArgumentException('For serial-tracked quotation items, serial count plus serial-less quantity must match quantity.');
            }

            $lineTotal = round($quantity * (float) $item['unit_price'], 2);
            $subtotal += $lineTotal;
            $items[] = [
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product_name'],
                'product_type' => $product?->product_type,
                'quantity' => $quantity,
                'unit_price' => $item['unit_price'],
                'total' => $lineTotal,
                'serial_numbers' => $serials === [] ? null : implode(', ', $serials),
                'serialless_quantity' => $serialless,
                'warranty_days' => $product?->warranty_days,
                'service_guarantee_days' => $product?->service_guarantee_days,
            ];
        }

        $discount = $this->adjustment($subtotal, $data['discount'], $data['discount_type']);
        $vat = $this->adjustment(max(0, $subtotal - $discount), $data['vat'], $data['vat_type']);
        $data['discount_amount'] = $discount;
        $data['vat_amount'] = $vat;

        return [$customerId, $items, $subtotal, max(0, $subtotal - $discount + $vat), $data];
    }

    private function quotationAttributes(int $customerId, float $subtotal, float $total, array $data): array
    {
        return [
            'customer_id' => $customerId,
            'quotation_date' => $data['quotation_date'],
            'valid_until' => $data['valid_until'] ?? null,
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
            'payment_note' => $data['payment_note'] ?? null,
            'public_note' => $data['public_note'] ?? null,
            'show_public_note' => (bool) ($data['show_public_note'] ?? false),
            'private_note' => $data['private_note'] ?? null,
        ];
    }

    private function adjustment(float $base, mixed $value, string $type): float
    {
        return $type === 'percent' ? round($base * (float) $value / 100, 2) : round((float) $value, 2);
    }

    private function productSuggestionData(?Quotation $quotation = null)
    {
        $defaultWarehouseId = app(InventoryService::class)->defaultWarehouse()->id;
        $quotedSerials = $quotation
            ? $quotation->items->pluck('serial_numbers')->filter()->flatMap(fn ($value) => app(SerialNumberParser::class)->parse($value))->values()
            : collect();

        return Product::query()
            ->with(['serials' => function ($query) use ($quotedSerials, $defaultWarehouseId): void {
                $query->where(function ($query) use ($quotedSerials, $defaultWarehouseId): void {
                    $query->where(function ($query) use ($defaultWarehouseId): void {
                        $query->where('status', 'in_stock')
                            ->where('warehouse_id', $defaultWarehouseId);
                    });
                    if ($quotedSerials->isNotEmpty()) {
                        $query->orWhereIn('serial_number', $quotedSerials);
                    }
                })->orderBy('serial_number');
            }])
            ->orderBy('name')
            ->get()
            ->map(fn (Product $product): array => [
                'id' => $product->id, 'name' => $product->name, 'sku' => $product->sku,
                'barcode' => $product->barcode, 'brand' => $product->brand,
                'sale_price' => (float) $product->sale_price, 'stock_quantity' => (int) $product->stock_quantity,
                'product_type' => $product->product_type, 'service_guarantee_days' => $product->service_guarantee_days,
                'track_inventory' => (bool) $product->track_inventory, 'track_serials' => (bool) $product->track_serial_numbers,
                'serials' => $product->serials->map(fn (ProductSerial $serial): array => [
                    'serial_number' => $serial->serial_number,
                    'warranty_until' => $serial->warranty_until?->format('Y-m-d'),
                    'status' => $serial->status,
                ])->values(),
            ])->values();
    }
}
