<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
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
            'products' => $query->latest()
                ->paginate($this->perPage($request))
                ->appends($request->query()),
            ...$this->productTaxonomyOptions(),
        ]);
    }

    public function create()
    {
        return view('products.create', $this->productTaxonomyOptions());
    }

    public function show(Request $request, Product $product)
    {
        $stockMovements = $product->stockMovements()
            ->latest()
            ->paginate($this->perPage($request))
            ->appends($request->query());
        $serials = $product->serials()
            ->with('purchaseBill')
            ->latest()
            ->limit(200)
            ->get();
        $serialGroups = $product->serials()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('products.show', compact('product', 'stockMovements', 'serials', 'serialGroups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:products,barcode'],
            'brand' => ['nullable', 'string', 'max:100'],
            'product_category_id' => ['nullable', 'exists:product_categories,id'],
            'track_inventory' => ['nullable', 'boolean'],
            'track_serial_numbers' => ['nullable', 'boolean'],
            'warranty_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_alert' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['track_inventory'] = $request->has('track_inventory') ? $request->boolean('track_inventory') : true;
        $data['track_serial_numbers'] = $data['track_inventory'] && $request->boolean('track_serial_numbers');
        $data['warranty_days'] = isset($data['warranty_days']) && $data['warranty_days'] !== ''
            ? (int) $data['warranty_days']
            : null;

        if (! $data['track_inventory']) {
            $data['stock_quantity'] = 0;
            $data['low_stock_alert'] = 0;
        } else {
            $data['stock_quantity'] = (int) ($data['stock_quantity'] ?? 0);
            $data['low_stock_alert'] = (int) ($data['low_stock_alert'] ?? 5);
        }

        $data = $this->syncCategoryLabels($data);

        Product::create($data);

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
        ]);

        try {
            DB::transaction(function () use ($data, $inventoryService, $product): void {
                $quantity = (int) $data['quantity'];
                $serialNumbers = app(SerialNumberParser::class)->parse($data['serial_numbers'] ?? '');

                if ($product->track_serial_numbers && in_array($data['type'], ['out', 'use'], true)) {
                    if (count($serialNumbers) !== $quantity) {
                        throw new InvalidArgumentException('Serial number count must match quantity for serial-tracked stock out/use.');
                    }

                    $foundSerials = $product->serials()
                        ->whereIn('serial_number', $serialNumbers)
                        ->where('status', 'in_stock')
                        ->pluck('serial_number')
                        ->all();

                    $missingSerials = array_values(array_diff($serialNumbers, $foundSerials));

                    if ($missingSerials !== []) {
                        throw new InvalidArgumentException('These serials are not available in house: '.implode(', ', $missingSerials));
                    }
                }

                $inventoryService->moveStock($product, $data['type'], $quantity, $data['reason'] ?? null);

                if ($product->track_serial_numbers && $serialNumbers !== []) {
                    if ($data['type'] === 'in') {
                        foreach ($serialNumbers as $serialNumber) {
                            $product->serials()->firstOrCreate(
                                ['serial_number' => $serialNumber],
                                ['status' => 'in_stock', 'note' => $data['reason'] ?? null],
                            );
                        }
                    } elseif (in_array($data['type'], ['out', 'use'], true)) {
                        $product->serials()
                            ->whereIn('serial_number', $serialNumbers)
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
