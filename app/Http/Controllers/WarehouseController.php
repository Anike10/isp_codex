<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductWarehouseStock;
use App\Models\Invoice;
use App\Models\PurchaseBill;
use App\Models\SaleReturn;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Support\SerialNumberParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $warehouses = Warehouse::query()
            ->withSum('stocks', 'quantity')
            ->withCount(['stocks as stocked_products_count' => fn ($query) => $query->where('quantity', '>', 0)])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->query('search'));
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->query('status') === 'active'))
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

    public function edit(Warehouse $warehouse)
    {
        return view('warehouses.edit', compact('warehouse'));
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $request->merge(['code' => Str::upper(trim((string) $request->input('code')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:30', Rule::unique('warehouses', 'code')->ignore($warehouse->id)],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);
        $data['is_default'] = $request->boolean('is_default');

        if ($data['is_default']) {
            $data['is_active'] = true;
        }

        DB::transaction(function () use ($warehouse, $data): void {
            if ($data['is_default']) {
                Warehouse::query()->whereKeyNot($warehouse->id)->update(['is_default' => false]);
            }

            $warehouse->update($data);
        });

        return redirect()->route('warehouses.index')->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Warehouse $warehouse)
    {
        if ($warehouse->is_default) {
            return back()->withErrors(['warehouse' => 'The default warehouse cannot be deleted. Make another warehouse default first.']);
        }

        $hasDependencies = $warehouse->stocks()->where('quantity', '>', 0)->exists()
            || $warehouse->usedStocks()->exists()
            || $warehouse->serials()->exists()
            || $warehouse->movements()->exists()
            || StockMovement::query()->where('related_warehouse_id', $warehouse->id)->exists()
            || DB::table('employee_asset_assignments')->where('warehouse_id', $warehouse->id)->exists()
            || DB::table('employee_asset_returns')->where('warehouse_id', $warehouse->id)->exists();

        if ($hasDependencies) {
            return back()->withErrors(['warehouse' => 'A warehouse with stock, serials, asset records, or movement history cannot be deleted. Set it to inactive instead.']);
        }

        $warehouse->delete();

        return redirect()->route('warehouses.index')->with('success', 'Warehouse deleted successfully.');
    }

    public function show(Request $request, Warehouse $warehouse)
    {
        $stocks = ProductWarehouseStock::query()
            ->where('warehouse_id', $warehouse->id)
            ->where('quantity', '>', 0)
            ->with(['product.serials' => fn ($query) => $query
                ->where('warehouse_id', $warehouse->id)
                ->where('status', 'in_stock')])
            ->when($request->filled('search'), function ($query) use ($request, $warehouse): void {
                $search = trim((string) $request->query('search'));
                $query->whereHas('product', fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhereHas('serials', fn ($query) => $query
                        ->where('warehouse_id', $warehouse->id)
                        ->where('status', 'in_stock')
                        ->where('serial_number', 'like', "%{$search}%")));
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
        $referenceLinks = $this->stockReferenceLinks($recentMovements->pluck('reference_no')->filter()->unique()->values()->all());

        return view('warehouses.show', compact('warehouse', 'stocks', 'recentMovements', 'referenceLinks'));
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
            'name' => $product->name,
            'sku' => $product->sku,
            'label' => $product->name.' ('.$product->sku.')',
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
        if (! $request->has('items') && $request->filled('product_id')) {
            $request->merge(['items' => [[
                'product_id' => $request->input('product_id'),
                'quantity' => $request->input('quantity'),
                'serial_numbers' => $request->input('serial_numbers'),
                'serialless_quantity' => $request->input('serialless_quantity'),
            ]]]);
        }

        $data = $request->validate([
            'from_warehouse_id' => ['required', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'different:from_warehouse_id', 'exists:warehouses,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.serial_numbers' => ['nullable', 'string'],
            'items.*.serialless_quantity' => ['nullable', 'integer', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $items = collect($data['items'])->map(fn (array $item): array => [
                'product' => Product::findOrFail($item['product_id']),
                'quantity' => (int) $item['quantity'],
                'serial_numbers' => app(SerialNumberParser::class)->parse($item['serial_numbers'] ?? ''),
                'serialless_quantity' => (int) ($item['serialless_quantity'] ?? 0),
            ])->all();
            $inventoryService->transferMany(
                Warehouse::where('is_active', true)->findOrFail($data['from_warehouse_id']),
                Warehouse::where('is_active', true)->findOrFail($data['to_warehouse_id']),
                $items,
                $data['reason'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors([
                'items' => $exception->getMessage(),
                'quantity' => $exception->getMessage(),
            ]);
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
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->query('search'));
                $query->where(function ($query) use ($search) {
                    $query->where('reference_no', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%")
                        ->orWhere('serial_numbers', 'like', "%{$search}%")
                        ->orWhereHas('product', fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%")
                            ->orWhereHas('serials', fn ($query) => $query->where('serial_number', 'like', "%{$search}%")))
                        ->orWhereHas('warehouse', fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%"))
                        ->orWhereHas('relatedWarehouse', fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest()
            ->paginate($this->perPage($request))
            ->appends($request->query());
        $referenceLinks = $this->stockReferenceLinks($movements->getCollection()->pluck('reference_no')->filter()->unique()->values()->all());
        $warehouses = Warehouse::query()->orderBy('name')->get();
        $products = Product::query()->where('track_inventory', true)->orderBy('name')->get();

        return view('warehouses.movements', compact('movements', 'warehouses', 'products', 'referenceLinks'));
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
}
