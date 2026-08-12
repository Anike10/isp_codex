<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSerial;
use App\Models\PurchaseBill;
use App\Models\SaleReturn;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Support\SerialNumberParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class ProductController extends Controller
{
    public function index(Request $request, InventoryService $inventoryService)
    {
        $serialSearch = trim((string) $request->query('serial_search', ''));

        $query = Product::query()
            ->when($request->filled('brand'), fn ($query) => $query->where('brand', $request->query('brand')))
            ->when($request->filled('product_category_id'), function ($query) use ($request): void {
                $categoryIds = $this->categoryAndDescendantIds((int) $request->query('product_category_id'));
                $query->whereIn('product_category_id', $categoryIds);
            })
            ->when($request->query('stock_state') === 'low', fn ($query) => $query->whereColumn('stock_quantity', '<=', 'low_stock_alert')->where('track_inventory', true))
            ->when($request->query('stock_state') === 'out', fn ($query) => $query->where('stock_quantity', '<=', 0)->where('track_inventory', true))
            ->when($request->query('stock_state') === 'tracked', fn ($query) => $query->where('track_inventory', true))
            ->when($request->query('stock_state') === 'serial', fn ($query) => $query->where('track_serial_numbers', true))
            ->when($serialSearch !== '', fn ($query) => $query->whereHas('serials', fn ($query) => $query->where('serial_number', 'like', "%{$serialSearch}%")))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->query('search'));
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('subcategory', 'like', "%{$search}%");
                });
            });
        $serialTraceSerials = $this->serialTraceSerials($serialSearch);
        $serialTraceMovements = $this->serialTraceMovements($serialSearch, $serialTraceSerials->pluck('product_id')->unique()->values()->all());
        $serialTraceReferenceLinks = $this->stockReferenceLinks($serialTraceMovements->pluck('reference_no')->filter()->unique()->values()->all());

        return view('products.index', [
            'products' => $query->with(['warehouseStocks', 'serials' => fn ($query) => $query
                ->where('status', 'in_stock')
                ->orderBy('serial_number')])
                ->latest()
                ->paginate($this->perPage($request))
                ->appends($request->query()),
            ...$this->productTaxonomyOptions(),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(),
            'defaultWarehouse' => $inventoryService->defaultWarehouse(),
            'serialSearch' => $serialSearch,
            'serialTraceSerials' => $serialTraceSerials,
            'serialTraceMovements' => $serialTraceMovements,
            'serialTraceReferenceLinks' => $serialTraceReferenceLinks,
        ]);
    }

    public function create()
    {
        return view('products.create', [
            'suggestedSku' => $this->nextSku(),
            ...$this->productTaxonomyOptions(),
        ]);
    }

    public function edit(Product $product)
    {
        $product->load('productCategory.parent.parent.parent');

        return view('products.create', [
            'product' => $product,
            ...$this->productTaxonomyOptions(),
        ]);
    }

    public function show(Request $request, Product $product)
    {
        $stockMovements = $product->stockMovements()
            ->with(['warehouse', 'relatedWarehouse'])
            ->latest()
            ->paginate($this->perPage($request))
            ->appends($request->query());
        $referenceLinks = $this->stockReferenceLinks($stockMovements->getCollection()->pluck('reference_no')->filter()->unique()->values()->all());
        $serials = $product->serials()
            ->with(['purchaseBill', 'customer', 'warehouse'])
            ->latest()
            ->limit(200)
            ->get();
        $serialGroups = $product->serials()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $seriallessInStock = $product->track_serial_numbers
            ? max(0, (int) $product->stock_quantity - (int) ($serialGroups['in_stock'] ?? 0))
            : 0;

        $warehouses = Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
        $warehouseStocks = $product->warehouseStocks()->with('warehouse')->get();

        return view('products.show', compact('product', 'stockMovements', 'serials', 'serialGroups', 'seriallessInStock', 'warehouses', 'warehouseStocks', 'referenceLinks'));
    }

    public function store(Request $request, InventoryService $inventoryService)
    {
        $data = $this->validatedProductData($request);

        $openingStock = (int) ($data['stock_quantity'] ?? 0);
        $data['stock_quantity'] = 0;
        $product = Product::create($data);

        if ($product->track_inventory && $openingStock > 0) {
            $inventoryService->moveStock($product, 'in', $openingStock, 'Opening stock');
        }

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validatedProductData($request, $product);
        unset($data['stock_quantity']);
        unset($data['serial_conversion_serial_numbers'], $data['serial_conversion_serialless_quantity']);
        $isConvertingToSerial = ! $product->track_serial_numbers && $data['track_serial_numbers'];

        if (! $data['track_inventory'] && ((int) $product->stock_quantity > 0 || $product->stockMovements()->exists())) {
            return back()->withInput()->withErrors([
                'track_inventory' => 'Inventory tracking cannot be disabled while this product has stock or movement history.',
            ]);
        }

        if (! $data['track_serial_numbers'] && $product->serials()->exists()) {
            return back()->withInput()->withErrors([
                'track_serial_numbers' => 'Serial tracking cannot be disabled after serial numbers have been recorded.',
            ]);
        }

        try {
            $conversionSerials = $this->validatedSerialConversionData($request, $product, $isConvertingToSerial);

            DB::transaction(function () use ($product, $data, $isConvertingToSerial, $conversionSerials): void {
                $product->update($data);

                if (! $isConvertingToSerial || $conversionSerials === []) {
                    return;
                }

                $defaultWarehouseId = Warehouse::query()->where('is_default', true)->value('id');

                foreach ($conversionSerials as $serialNumber) {
                    ProductSerial::create([
                        'product_id' => $product->id,
                        'warehouse_id' => $defaultWarehouseId,
                        'serial_number' => $serialNumber,
                        'status' => 'in_stock',
                        'note' => 'Added when product was converted to serial-tracked.',
                    ]);
                }
            });
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors([
                'serial_conversion' => $exception->getMessage(),
            ]);
        }

        return redirect()->route('products.show', $product)->with('success', 'Product updated successfully.');
    }

    public function moveStock(Request $request, Product $product, InventoryService $inventoryService)
    {
        if (! $product->track_inventory) {
            return back()->withErrors(['quantity' => 'This product does not track inventory.']);
        }

        $data = $request->validate([
            'type' => ['required', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
            'serial_numbers' => ['nullable', 'string'],
            'serialless_quantity' => ['nullable', 'integer', 'min:0'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
        ]);

        $warehouse = isset($data['warehouse_id'])
            ? Warehouse::query()->where('is_active', true)->findOrFail($data['warehouse_id'])
            : $inventoryService->defaultWarehouse();

        try {
            DB::transaction(function () use ($data, $inventoryService, $product, $warehouse): void {
                $quantity = (int) $data['quantity'];
                $serialNumbers = app(SerialNumberParser::class)->parse($data['serial_numbers'] ?? '');
                $seriallessQuantity = (int) ($data['serialless_quantity'] ?? 0);

                if (! $product->track_serial_numbers) {
                    $seriallessQuantity = 0;
                } elseif (count($serialNumbers) + $seriallessQuantity !== $quantity) {
                    throw new InvalidArgumentException('For serial-tracked stock movements, serial count plus serial-less quantity must match quantity.');
                }

                if ($product->track_serial_numbers && in_array($data['type'], ['out', 'use'], true) && $serialNumbers !== []) {
                    if (count($serialNumbers) > $quantity) {
                        throw new InvalidArgumentException('Serial number count cannot be greater than quantity.');
                    }

                    $foundSerials = $product->serials()
                        ->where('warehouse_id', $warehouse->id)
                        ->whereIn('serial_number', $serialNumbers)
                        ->where('status', 'in_stock')
                        ->pluck('serial_number')
                        ->all();

                    $missingSerials = array_values(array_diff($serialNumbers, $foundSerials));

                    if ($missingSerials !== []) {
                        throw new InvalidArgumentException('These serials are not available in the selected warehouse: '.implode(', ', $missingSerials));
                    }
                }

                $inventoryService->moveStock($product, $data['type'], $quantity, $data['reason'] ?? null, null, $seriallessQuantity, $warehouse, $serialNumbers);

                if ($product->track_serial_numbers && $serialNumbers !== []) {
                    if ($data['type'] === 'in') {
                        foreach ($serialNumbers as $serialNumber) {
                            $product->serials()->firstOrCreate(
                                ['serial_number' => $serialNumber],
                                ['warehouse_id' => $warehouse->id, 'status' => 'in_stock', 'note' => $data['reason'] ?? null],
                            );
                            $product->serials()
                                ->where('serial_number', $serialNumber)
                                ->where('status', 'in_stock')
                                ->update(['warehouse_id' => $warehouse->id]);
                        }
                    } elseif (in_array($data['type'], ['out', 'use'], true)) {
                        $product->serials()
                            ->whereIn('serial_number', $serialNumbers)
                            ->where('warehouse_id', $warehouse->id)
                            ->update([
                                'status' => $data['type'] === 'use' ? 'used' : 'out',
                                'note' => $data['reason'] ?? null,
                            ]);
                    }
                }
            });
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return redirect()->route('products.index')->with('success', 'Stock updated successfully.');
    }

    private function productTaxonomyOptions(): array
    {
        return [
            'brands' => Product::query()->whereNotNull('brand')->where('brand', '!=', '')->distinct()->orderBy('brand')->pluck('brand'),
            'categoryTree' => ProductCategory::query()->with('children.children.children.children')->whereNull('parent_id')->orderBy('name')->get(),
            'categoryOptions' => ProductCategory::query()->with('parent.parent.parent')->orderBy('name')->get(),
        ];
    }

    private function nextSku(): string
    {
        $nextSequence = Product::query()
            ->where('sku', 'like', 'SKU-%')
            ->pluck('sku')
            ->reduce(function (int $maximum, string $sku): int {
                return preg_match('/^SKU-(\d+)$/', $sku, $matches)
                    ? max($maximum, (int) $matches[1])
                    : $maximum;
            }, 0) + 1;

        do {
            $sku = 'SKU-'.str_pad((string) $nextSequence, 6, '0', STR_PAD_LEFT);
            $nextSequence++;
        } while (Product::query()->where('sku', $sku)->exists());

        return $sku;
    }

    private function validatedProductData(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product)],
            'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($product)],
            'brand' => ['nullable', 'string', 'max:100'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'product_type' => ['nullable', 'in:stock,serial_stock,consumable,service,warranty'],
            'track_inventory' => ['nullable', 'boolean'],
            'track_serial_numbers' => ['nullable', 'boolean'],
            'warranty_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'service_guarantee_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_alert' => ['nullable', 'integer', 'min:0'],
            'serial_conversion_serial_numbers' => ['nullable', 'string'],
            'serial_conversion_serialless_quantity' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['product_type'] = $data['product_type'] ?? 'stock';
        $data['track_inventory'] = in_array($data['product_type'], ['stock', 'serial_stock', 'consumable'], true)
            && ($request->has('track_inventory') ? $request->boolean('track_inventory') : true);
        $data['track_serial_numbers'] = $data['product_type'] === 'serial_stock'
            || ($data['track_inventory'] && $request->boolean('track_serial_numbers'));
        if ($data['track_serial_numbers']) {
            $data['product_type'] = 'serial_stock';
        }
        $data['warranty_days'] = isset($data['warranty_days']) && $data['warranty_days'] !== ''
            ? (int) $data['warranty_days']
            : null;
        $data['service_guarantee_days'] = isset($data['service_guarantee_days']) && $data['service_guarantee_days'] !== ''
            ? (int) $data['service_guarantee_days']
            : null;

        if (! $data['track_inventory']) {
            $data['stock_quantity'] = 0;
            $data['low_stock_alert'] = 0;
            $data['track_serial_numbers'] = false;
        } else {
            $data['stock_quantity'] = (int) ($data['stock_quantity'] ?? $product?->stock_quantity ?? 0);
            $data['low_stock_alert'] = (int) ($data['low_stock_alert'] ?? 5);
        }

        return $this->syncCategoryLabels($data);
    }

    private function validatedSerialConversionData(Request $request, Product $product, bool $isConvertingToSerial): array
    {
        if (! $isConvertingToSerial) {
            return [];
        }

        $stockQuantity = (int) $product->stock_quantity;
        $serialNumbers = app(SerialNumberParser::class)->parse($request->input('serial_conversion_serial_numbers', ''));
        $seriallessQuantity = (int) $request->input('serial_conversion_serialless_quantity', 0);

        if ($stockQuantity <= 0) {
            if ($serialNumbers !== [] || $seriallessQuantity > 0) {
                throw new InvalidArgumentException('This product has no current stock. Add new serial stock through Purchase Bill or Move Stock.');
            }

            return [];
        }

        if (count($serialNumbers) + $seriallessQuantity !== $stockQuantity) {
            throw new InvalidArgumentException('This product has '.$stockQuantity.' existing stock. Before making it serial-tracked, enter serials plus serial-less quantity for all '.$stockQuantity.' unit(s). Current count: '.count($serialNumbers).' serial(s) + '.$seriallessQuantity.' serial-less.');
        }

        if ($serialNumbers === []) {
            return [];
        }

        $existingSerials = ProductSerial::query()
            ->where('product_id', $product->id)
            ->whereIn('serial_number', $serialNumbers)
            ->pluck('serial_number')
            ->all();

        if ($existingSerials !== []) {
            throw new InvalidArgumentException('These serials already exist for this product: '.implode(', ', $existingSerials));
        }

        return $serialNumbers;
    }

    private function syncCategoryLabels(array $data): array
    {
        $data['category'] = null;
        $data['subcategory'] = null;

        if (empty($data['product_category_id'])) {
            return $data;
        }

        $category = ProductCategory::query()->with('parent.parent.parent')->findOrFail($data['product_category_id']);
        $path = $category->pathNames();

        $data['category'] = $path[0] ?? null;
        $data['subcategory'] = count($path) > 1 ? implode(' / ', array_slice($path, 1)) : null;

        return $data;
    }

    private function categoryAndDescendantIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $children = ProductCategory::query()->where('parent_id', $categoryId)->pluck('id');

        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->categoryAndDescendantIds((int) $childId));
        }

        return array_values(array_unique($ids));
    }

    private function stockReferenceLinks(array $references): array
    {
        if ($references === []) {
            return [];
        }

        $links = [];

        foreach (PurchaseBill::query()
            ->whereIn('bill_no', $references)
            ->get(['id', 'bill_no']) as $purchaseBill) {
            $links[$purchaseBill->bill_no] = route('purchase-bills.show', $purchaseBill);
        }

        foreach (Invoice::query()
            ->whereIn('invoice_no', $references)
            ->get(['id', 'invoice_no']) as $invoice) {
            $links[$invoice->invoice_no] = route('invoices.show', $invoice);
        }

        foreach (SaleReturn::query()
            ->whereIn('return_no', $references)
            ->get(['id', 'return_no']) as $saleReturn) {
            $links[$saleReturn->return_no] = route('sale-returns.show', $saleReturn);
        }

        return $links;
    }

    private function serialTraceSerials(string $serialSearch)
    {
        if ($serialSearch === '') {
            return collect();
        }

        return ProductSerial::query()
            ->with(['product', 'warehouse', 'purchaseBill.party', 'customer', 'invoice.customer', 'invoiceItem.saleReturnItems.saleReturn'])
            ->where('serial_number', 'like', "%{$serialSearch}%")
            ->orderBy('serial_number')
            ->limit(50)
            ->get();
    }

    private function serialTraceMovements(string $serialSearch, array $productIds)
    {
        if ($serialSearch === '' || $productIds === []) {
            return collect();
        }

        return StockMovement::query()
            ->with(['product', 'warehouse', 'relatedWarehouse'])
            ->whereIn('product_id', $productIds)
            ->where('serial_numbers', 'like', "%{$serialSearch}%")
            ->oldest()
            ->limit(100)
            ->get();
    }
}
