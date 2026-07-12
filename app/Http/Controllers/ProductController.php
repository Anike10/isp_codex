<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Support\SerialNumberParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
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

        return view('products.index', [
            'products' => $query->with(['warehouseStocks', 'serials' => fn ($query) => $query
                ->where('status', 'in_stock')
                ->orderBy('serial_number')])
                ->latest()
                ->paginate($this->perPage($request))
                ->appends($request->query()),
            ...$this->productTaxonomyOptions(),
            'warehouses' => Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get(),
            'defaultWarehouse' => Warehouse::query()->where('is_default', true)->firstOrFail(),
        ]);
    }

    public function create()
    {
        return view('products.create', $this->productTaxonomyOptions());
    }

    public function show(Request $request, Product $product)
    {
        $stockMovements = $product->stockMovements()
            ->with(['warehouse', 'relatedWarehouse'])
            ->latest()
            ->paginate($this->perPage($request))
            ->appends($request->query());
        $serials = $product->serials()
            ->with(['purchaseBill', 'customer', 'warehouse'])
            ->latest()
            ->limit(200)
            ->get();
        $serialGroups = $product->serials()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $warehouses = Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
        $warehouseStocks = $product->warehouseStocks()->with('warehouse')->get();

        return view('products.show', compact('product', 'stockMovements', 'serials', 'serialGroups', 'warehouses', 'warehouseStocks'));
    }

    public function store(Request $request, InventoryService $inventoryService)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
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
            $data['stock_quantity'] = (int) ($data['stock_quantity'] ?? 0);
            $data['low_stock_alert'] = (int) ($data['low_stock_alert'] ?? 5);
        }

        $data = $this->syncCategoryLabels($data);

        $openingStock = (int) ($data['stock_quantity'] ?? 0);
        $data['stock_quantity'] = 0;
        $product = Product::create($data);

        if ($product->track_inventory && $openingStock > 0) {
            $inventoryService->moveStock($product, 'in', $openingStock, 'Opening stock');
        }

        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function moveStock(Request $request, Product $product, InventoryService $inventoryService)
    {
        if (! $product->track_inventory) {
            return back()->withErrors(['quantity' => 'This product does not track inventory.']);
        }

        $data = $request->validate([
            'type' => ['required', 'in:in,out,use'],
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
}
