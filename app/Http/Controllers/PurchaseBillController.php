<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSerial;
use App\Models\PurchaseBill;
use App\Observers\RecordVersionObserver;
use App\Services\InventoryService;
use App\Services\RecordVersionService;
use App\Support\SerialNumberParser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Throwable;

class PurchaseBillController extends Controller
{
    public function index(Request $request)
    {
        return view('purchase_bills.index', [
            'purchaseBills' => PurchaseBill::query()
                ->with('party')
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = trim((string) $request->query('search'));
                    $query->where(function ($query) use ($search) {
                        $query->where('bill_no', 'like', "%{$search}%")
                            ->orWhere('note', 'like', "%{$search}%")
                            ->orWhereHas('party', fn ($query) => $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('connection_id', 'like', "%{$search}%"))
                            ->orWhereHas('items.serials', fn ($query) => $query->where('serial_number', 'like', "%{$search}%"))
                            ->orWhereHas('items.product', fn ($query) => $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%"));
                    });
                })
                ->when($request->filled('party_id'), fn ($query) => $query->where('party_id', $request->integer('party_id')))
                ->when($request->filled('from'), fn ($query) => $query->whereDate('purchase_date', '>=', $request->date('from')))
                ->when($request->filled('to'), fn ($query) => $query->whereDate('purchase_date', '<=', $request->date('to')))
                ->when($request->filled('min_amount'), fn ($query) => $query->where('subtotal', '>=', (float) $request->query('min_amount')))
                ->when($request->filled('max_amount'), fn ($query) => $query->where('subtotal', '<=', (float) $request->query('max_amount')))
                ->latest('purchase_date')
                ->latest()
                ->paginate($this->perPage($request))
                ->appends($request->query()),
            'vendors' => Customer::query()->where('is_vendor', true)->orderBy('name')->get(['id', 'name', 'phone']),
        ]);
    }

    public function create()
    {
        return view('purchase_bills.create', [
            ...$this->formData(),
            'defaultBillNo' => $this->nextBillNo(),
        ]);
    }

    public function store(Request $request, InventoryService $inventoryService)
    {
        $document = null;

        try {
            $data = $this->validatePurchaseBillData($request);
            $document = $this->storeDocument($data['document'] ?? null);

            DB::transaction(function () use ($data, $inventoryService, $document): void {
                $purchaseBill = PurchaseBill::create([
                    'party_id' => $this->resolveVendorPartyId($data),
                    'bill_no' => $data['bill_no'],
                    'purchase_date' => $data['purchase_date'],
                    'subtotal' => 0,
                    'note' => $data['note'] ?? null,
                    'document_path' => $document['path'] ?? null,
                    'document_name' => $document['name'] ?? null,
                    'document_mime' => $document['mime'] ?? null,
                ]);

                $subtotal = $this->applyPurchaseItems($purchaseBill, $data, $inventoryService);
                RecordVersionObserver::withoutRecording(fn () => $purchaseBill->update(['subtotal' => $subtotal]));
            });
        } catch (InvalidArgumentException $exception) {
            $this->deleteDocument($document['path'] ?? null);

            return back()->withInput()->withErrors(['items' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            $this->deleteDocument($document['path'] ?? null);

            throw $exception;
        }

        return redirect()->route('purchase-bills.index')->with('success', 'Purchase bill saved and stock updated.');
    }

    public function edit(PurchaseBill $purchaseBill)
    {
        if ($purchaseBill->isFinalized()) {
            return redirect()->route('purchase-bills.show', $purchaseBill)->withErrors([
                'purchase_bill' => 'Finalized purchase bills cannot be edited.',
            ]);
        }

        $purchaseBill->load(['party', 'items.product', 'items.serials']);

        return view('purchase_bills.create', [
            ...$this->formData(),
            'purchaseBill' => $purchaseBill,
            'defaultBillNo' => $purchaseBill->bill_no,
        ]);
    }

    public function update(Request $request, PurchaseBill $purchaseBill, InventoryService $inventoryService, RecordVersionService $recordVersionService)
    {
        if ($purchaseBill->isFinalized()) {
            return redirect()->route('purchase-bills.show', $purchaseBill)->withErrors([
                'purchase_bill' => 'Finalized purchase bills cannot be edited.',
            ]);
        }

        $becameFinalized = false;
        $newDocument = null;
        $oldDocumentPath = $purchaseBill->document_path;

        try {
            $data = $this->validatePurchaseBillData($request, $purchaseBill);
            $newDocument = $this->storeDocument($data['document'] ?? null);

            DB::transaction(function () use (&$purchaseBill, $data, $inventoryService, $recordVersionService, &$becameFinalized, $newDocument): void {
                $purchaseBill = PurchaseBill::query()
                    ->with(['items.product', 'items.serials'])
                    ->whereKey($purchaseBill->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($purchaseBill->isFinalized()) {
                    $becameFinalized = true;

                    return;
                }

                $oldSnapshot = $recordVersionService->snapshot($purchaseBill, ['party', 'items.product', 'items.serials']);
                $this->restorePurchaseInventory($purchaseBill, $inventoryService);

                $updates = [
                    'party_id' => $this->resolveVendorPartyId($data),
                    'bill_no' => $data['bill_no'],
                    'purchase_date' => $data['purchase_date'],
                    'subtotal' => 0,
                    'note' => $data['note'] ?? null,
                ];

                if ($newDocument) {
                    $updates += [
                        'document_path' => $newDocument['path'],
                        'document_name' => $newDocument['name'],
                        'document_mime' => $newDocument['mime'],
                    ];
                }

                RecordVersionObserver::withoutRecording(fn () => $purchaseBill->update($updates));

                $purchaseBill->items()->delete();
                $subtotal = $this->applyPurchaseItems($purchaseBill, $data, $inventoryService);
                RecordVersionObserver::withoutRecording(fn () => $purchaseBill->update(['subtotal' => $subtotal]));

                $newSnapshot = $recordVersionService->snapshot($purchaseBill->refresh(), ['party', 'items.product', 'items.serials']);
                $recordVersionService->recordUpdate($purchaseBill, $oldSnapshot, $newSnapshot, [
                    'source' => 'purchase_bill_edit',
                    'bill_no' => $purchaseBill->bill_no,
                ]);
            });
        } catch (InvalidArgumentException $exception) {
            $this->deleteDocument($newDocument['path'] ?? null);

            return back()->withInput()->withErrors(['items' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            $this->deleteDocument($newDocument['path'] ?? null);

            throw $exception;
        }

        if ($becameFinalized) {
            $this->deleteDocument($newDocument['path'] ?? null);

            return redirect()->route('purchase-bills.show', $purchaseBill)->withErrors([
                'purchase_bill' => 'Finalized purchase bills cannot be edited.',
            ]);
        }

        if ($newDocument && $oldDocumentPath && $oldDocumentPath !== $newDocument['path']) {
            $this->deleteDocument($oldDocumentPath);
        }

        return redirect()->route('purchase-bills.show', $purchaseBill)->with('success', 'Draft purchase bill updated successfully.');
    }

    public function finalize(PurchaseBill $purchaseBill, RecordVersionService $recordVersionService)
    {
        DB::transaction(function () use (&$purchaseBill, $recordVersionService): void {
            $purchaseBill = PurchaseBill::query()->whereKey($purchaseBill->id)->lockForUpdate()->firstOrFail();

            if ($purchaseBill->isFinalized()) {
                return;
            }

            $oldSnapshot = $recordVersionService->snapshot($purchaseBill, ['party', 'items.product', 'items.serials']);
            RecordVersionObserver::withoutRecording(fn () => $purchaseBill->update(['finalized_at' => now()]));
            $newSnapshot = $recordVersionService->snapshot($purchaseBill->refresh(), ['party', 'items.product', 'items.serials']);
            $recordVersionService->recordUpdate($purchaseBill, $oldSnapshot, $newSnapshot, [
                'source' => 'purchase_bill_finalize',
                'bill_no' => $purchaseBill->bill_no,
            ]);
        });

        return redirect()->route('purchase-bills.show', $purchaseBill)->with('success', 'Purchase bill finalized. Editing is now locked.');
    }

    public function show(PurchaseBill $purchaseBill)
    {
        $purchaseBill->load(['party', 'items.product', 'items.serials']);
        $versions = $purchaseBill->versions()->paginate(10, ['*'], 'history_page')->withQueryString();

        return view('purchase_bills.show', compact('purchaseBill', 'versions'));
    }

    public function document(PurchaseBill $purchaseBill)
    {
        abort_unless($purchaseBill->document_path && Storage::disk('local')->exists($purchaseBill->document_path), 404);

        return Storage::disk('local')->response(
            $purchaseBill->document_path,
            $purchaseBill->document_name ?: basename($purchaseBill->document_path),
            ['Cache-Control' => 'private, no-store'],
            'inline',
        );
    }

    private function validatePurchaseBillData(Request $request, ?PurchaseBill $purchaseBill = null): array
    {
        $data = $request->validate([
            'party_id' => ['nullable', Rule::exists('customers', 'id')->where('is_vendor', true)],
            'party_name' => ['nullable', 'string', 'max:255'],
            'bill_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('purchase_bills', 'bill_no')->ignore($purchaseBill?->id),
            ],
            'purchase_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.product_name' => ['required_without:items.*.product_id', 'nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'items.*.warranty_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'items.*.serial_numbers' => ['nullable', 'string'],
            'items.*.serialless_quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.track_serial_numbers' => ['nullable', 'boolean'],
        ]);

        $data['items'] = collect($data['items'])
            ->filter(fn (array $item): bool => (! empty($item['product_id']) || trim((string) ($item['product_name'] ?? '')) !== '') && (int) ($item['quantity'] ?? 0) > 0)
            ->values()
            ->all();

        if ($data['items'] === []) {
            throw new InvalidArgumentException('At least one product line is required.');
        }

        return $data;
    }

    private function formData(): array
    {
        $products = Product::query()->with('productCategory.parent.parent.parent')->orderBy('name')->get();

        return [
            'vendors' => Customer::query()->where('is_vendor', true)->orderBy('name')->get(),
            'vendorSuggestionData' => Customer::query()
                ->where('is_vendor', true)
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'connection_id'])
                ->map(fn (Customer $vendor): array => [
                    'id' => $vendor->id,
                    'name' => $vendor->name,
                    'phone' => $vendor->phone,
                    'connection_id' => $vendor->connection_id,
                ])
                ->values(),
            'products' => $products,
            'productSuggestionData' => $products->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'brand' => $product->brand,
                'category' => $product->category,
                'subcategory' => $product->subcategory,
                'category_ids' => $product->categoryIdPath(),
                'unit_price' => (float) $product->purchase_price,
                'track_serials' => (bool) $product->track_serial_numbers,
                'warranty_days' => $product->warranty_days,
            ])->values(),
            'brands' => Product::query()->whereNotNull('brand')->where('brand', '!=', '')->distinct()->orderBy('brand')->pluck('brand'),
            'categoryTree' => ProductCategory::query()->with('children.children.children.children')->whereNull('parent_id')->orderBy('name')->get(),
        ];
    }

    private function applyPurchaseItems(PurchaseBill $purchaseBill, array $data, InventoryService $inventoryService): float
    {
        $defaultWarehouseId = $inventoryService->defaultWarehouse()->id;
        $subtotal = 0.0;

        foreach ($data['items'] as $item) {
            $quantity = (int) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $serialNumbers = app(SerialNumberParser::class)->parse($item['serial_numbers'] ?? '');
            $seriallessQuantity = (int) ($item['serialless_quantity'] ?? 0);
            $product = $this->resolvePurchaseProduct($item, $serialNumbers, $seriallessQuantity);
            $warrantyMonths = isset($item['warranty_months']) && $item['warranty_months'] !== ''
                ? (int) $item['warranty_months']
                : null;
            $warrantyDays = $this->warrantyDays($item, $product, $warrantyMonths);

            if (count($serialNumbers) > $quantity) {
                $quantity = count($serialNumbers);
            }

            if (! $product->track_serial_numbers && $serialNumbers !== []) {
                throw new InvalidArgumentException('Serial numbers can only be added for serial-tracked products.');
            }

            if (! $product->track_serial_numbers) {
                $seriallessQuantity = 0;
            } elseif (count($serialNumbers) + $seriallessQuantity !== $quantity) {
                throw new InvalidArgumentException($product->name.' is serial-tracked. Enter serial numbers or Serial-less Qty for all '.$quantity.' unit(s). Current count: '.count($serialNumbers).' serial(s) + '.$seriallessQuantity.' serial-less.');
            }

            $lineTotal = $quantity * $unitPrice;
            $subtotal += $lineTotal;

            $billItem = $purchaseBill->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'serialless_quantity' => $seriallessQuantity,
                'unit_price' => $unitPrice,
                'total' => $lineTotal,
                'warranty_months' => $warrantyMonths,
                'warranty_days' => $warrantyDays,
            ]);

            if ($product->track_inventory) {
                $inventoryService->moveStock($product, 'in', $quantity, 'Purchase bill '.$purchaseBill->bill_no, $purchaseBill->bill_no, $seriallessQuantity, null, $serialNumbers);
            }

            foreach ($serialNumbers as $serialNumber) {
                ProductSerial::create([
                    'product_id' => $product->id,
                    'warehouse_id' => $defaultWarehouseId,
                    'purchase_bill_id' => $purchaseBill->id,
                    'purchase_bill_item_id' => $billItem->id,
                    'serial_number' => $serialNumber,
                    'warranty_until' => $warrantyDays ? Carbon::parse($data['purchase_date'])->addDays($warrantyDays)->toDateString() : null,
                    'status' => 'in_stock',
                ]);
            }
        }

        return $subtotal;
    }

    private function restorePurchaseInventory(PurchaseBill $purchaseBill, InventoryService $inventoryService): void
    {
        $purchaseBill->loadMissing(['items.product', 'items.serials']);

        foreach ($purchaseBill->items as $item) {
            $product = Product::query()->lockForUpdate()->find($item->product_id);

            if (! $product?->track_inventory) {
                continue;
            }

            $serialNumbers = $item->serials->pluck('serial_number')->all();

            foreach ($item->serials as $serial) {
                if ($serial->status !== 'in_stock') {
                    throw new InvalidArgumentException('Purchase bill cannot be edited because serial '.$serial->serial_number.' is already '.$serial->status.'.');
                }
            }

            $inventoryService->moveStock($product, 'out', (int) $item->quantity, 'Purchase bill edit restore '.$purchaseBill->bill_no, $purchaseBill->bill_no, (int) $item->serialless_quantity, null, $serialNumbers);
            ProductSerial::query()->where('purchase_bill_item_id', $item->id)->delete();
        }
    }

    private function warrantyDays(array $item, Product $product, ?int $warrantyMonths): ?int
    {
        if (isset($item['warranty_days']) && $item['warranty_days'] !== '') {
            return (int) $item['warranty_days'];
        }

        if ($product->warranty_days !== null) {
            return (int) $product->warranty_days;
        }

        return $warrantyMonths ? $warrantyMonths * 30 : null;
    }

    private function resolvePurchaseProduct(array $item, array $serialNumbers, int $seriallessQuantity): Product
    {
        if (! empty($item['product_id'])) {
            return Product::lockForUpdate()->findOrFail($item['product_id']);
        }

        $name = trim((string) ($item['product_name'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException('Product name is required when no existing product is selected.');
        }

        $existing = Product::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->lockForUpdate()
            ->first();

        if ($existing) {
            return $existing;
        }

        $unitPrice = (float) ($item['unit_price'] ?? 0);

        $tracksSerials = (bool) ($item['track_serial_numbers'] ?? false) || $serialNumbers !== [] || $seriallessQuantity > 0;

        return Product::create([
            'name' => $name,
            'sku' => $this->nextProductSku($name),
            'product_type' => $tracksSerials ? 'serial_stock' : 'stock',
            'track_inventory' => true,
            'track_serial_numbers' => $tracksSerials,
            'warranty_days' => isset($item['warranty_days']) && $item['warranty_days'] !== '' ? (int) $item['warranty_days'] : null,
            'purchase_price' => $unitPrice,
            'sale_price' => $unitPrice,
            'stock_quantity' => 0,
            'low_stock_alert' => 5,
        ]);
    }

    private function resolveVendorPartyId(array $data): ?int
    {
        if (! empty($data['party_id'])) {
            return (int) $data['party_id'];
        }

        $name = trim((string) ($data['party_name'] ?? ''));

        if ($name === '') {
            return null;
        }

        $vendor = Customer::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->lockForUpdate()
            ->first();

        if ($vendor) {
            if (! $vendor->is_vendor) {
                $vendor->forceFill(['is_vendor' => true])->save();
            }

            return $vendor->id;
        }

        return Customer::create([
            'name' => $name,
            'phone' => '',
            'connection_id' => null,
            'address' => '',
            'status' => 'active',
            'is_customer' => false,
            'is_vendor' => true,
        ])->id;
    }

    private function nextProductSku(string $name): string
    {
        $base = strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '-', $name));
        $base = trim($base, '-') ?: 'PRODUCT';
        $base = substr($base, 0, 24);

        do {
            $sku = $base.'-'.now()->format('His').'-'.random_int(100, 999);
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    private function nextBillNo(): string
    {
        $prefix = 'PB-'.now()->format('Y-m-');
        $count = PurchaseBill::query()->where('bill_no', 'like', $prefix.'%')->count() + 1;

        return $prefix.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }

    private function storeDocument(?UploadedFile $file): ?array
    {
        if (! $file) {
            return null;
        }

        return [
            'path' => $file->store('purchase-bill-documents/'.now()->format('Y/m'), 'local'),
            'name' => basename($file->getClientOriginalName()),
            'mime' => $file->getMimeType(),
        ];
    }

    private function deleteDocument(?string $path): void
    {
        if ($path) {
            Storage::disk('local')->delete($path);
        }
    }
}
