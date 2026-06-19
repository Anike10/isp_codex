<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Support\SerialNumberParser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::query()
            ->withSum('stocks', 'quantity')
            ->withCount(['stocks as stocked_products_count' => fn ($query) => $query->where('quantity', '>', 0)])
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('warehouses.index', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $request->merge(['code' => Str::upper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:30', 'unique:warehouses,code'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);
        $data['is_active'] = true;

        Warehouse::create($data);

        return redirect()->route('warehouses.index')->with('success', 'Warehouse created successfully.');
    }

    public function show(Request $request, Warehouse $warehouse)
    {
        $stocks = ProductWarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('quantity', '>', 0)
            ->with(['product.serials' => fn ($query) => $query
                ->where('warehouse_id', $warehouse->id)
                ->where('status', 'in_stock')])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->query('search'));
                $query->whereHas('product', fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%"));
            })
            ->orderByDesc('quantity')
            ->paginate($this->perPage($request))
            ->appends($request->query());
        $recentMovements = StockMovement::query()
            ->where('warehouse_id', $warehouse->id)
            ->with(['product', 'relatedWarehouse'])
            ->latest()
            ->limit(20)
            ->get();

        return view('warehouses.show', compact('warehouse', 'stocks', 'recentMovements'));
    }

    public function createTransfer()
    {
        $warehouses = Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
        $products = Product::query()
            ->where('track_inventory', true)
            ->with(['warehouseStocks', 'serials' => fn ($query) => $query->where('status', 'in_stock')->orderBy('serial_number')])
            ->orderBy('name')
            ->get();
        $productData = $products->map(fn (Product $product): array => [
            'id' => $product->id,
            'track_serials' => (bool) $product->track_serial_numbers,
            'stocks' => $product->warehouseStocks->pluck('quantity', 'warehouse_id'),
            'serials' => $product->serials->map(fn ($serial): array => [
                'number' => $serial->serial_number,
                'warehouse_id' => $serial->warehouse_id,
            ])->values(),
        ])->values();

        return view('warehouses.transfer', compact('warehouses', 'products', 'productData'));
    }

    public function storeTransfer(Request $request, InventoryService $inventoryService)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'different:from_warehouse_id', 'exists:warehouses,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'serial_numbers' => ['nullable', 'string'],
            'serialless_quantity' => ['nullable', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $inventoryService->transfer(
                Product::findOrFail($data['product_id']),
                Warehouse::where('is_active', true)->findOrFail($data['from_warehouse_id']),
                Warehouse::where('is_active', true)->findOrFail($data['to_warehouse_id']),
                (int) $data['quantity'],
                app(SerialNumberParser::class)->parse($data['serial_numbers'] ?? ''),
                (int) ($data['serialless_quantity'] ?? 0),
                $data['reason'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return redirect()->route('warehouses.show', $data['to_warehouse_id'])->with('success', 'Stock transferred successfully.');
    }

    public function movements(Request $request)
    {
        $request->validate([
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'type' => ['nullable', Rule::in(['in', 'out', 'use', 'transfer_in', 'transfer_out'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        $movements = StockMovement::query()
            ->with(['product', 'warehouse', 'relatedWarehouse'])
            ->when($request->filled('warehouse_id'), fn ($query) => $query->where('warehouse_id', $request->integer('warehouse_id')))
            ->when($request->filled('product_id'), fn ($query) => $query->where('product_id', $request->integer('product_id')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->query('type')))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest()
            ->paginate($this->perPage($request))
            ->appends($request->query());
        $warehouses = Warehouse::query()->orderBy('name')->get();
        $products = Product::query()->where('track_inventory', true)->orderBy('name')->get();

        return view('warehouses.movements', compact('movements', 'warehouses', 'products'));
    }
}
