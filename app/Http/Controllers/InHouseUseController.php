<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeAssetAssignment;
use App\Models\Product;
use App\Models\UsedProductWarehouseStock;
use App\Models\Warehouse;
use App\Services\EmployeeAssetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class InHouseUseController extends Controller
{
    public function index(Request $request)
    {
        return view('in_house_use.index', $this->formData($request));
    }

    public function employeeReport(Request $request)
    {
        $employees = Employee::query()
            ->whereHas('assetAssignments')
            ->with(['assetAssignments.employee', 'assetAssignments.product', 'assetAssignments.returns'])
            ->when($request->filled('employee_id'), fn ($query) => $query->whereKey($request->integer('employee_id')))
            ->orderBy('name')
            ->get();
        $employeeOptions = Employee::query()->orderBy('name')->get();

        return view('in_house_use.reports.employees', compact('employees', 'employeeOptions'));
    }

    public function usedStockReport(Request $request)
    {
        $usedStocks = UsedProductWarehouseStock::query()
            ->where('quantity', '>', 0)
            ->with(['product.serials' => fn ($query) => $query->where('status', 'used_in_stock')->orderBy('serial_number'), 'warehouse'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->query('search'));
                $query->whereHas('product', fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%"));
            })
            ->when($request->filled('warehouse_id'), fn ($query) => $query->where('warehouse_id', $request->integer('warehouse_id')))
            ->orderByDesc('quantity')
            ->get();
        $warehouses = Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();

        return view('in_house_use.reports.used_stock', compact('usedStocks', 'warehouses'));
    }

    public function historyReport(Request $request)
    {
        $assignments = EmployeeAssetAssignment::query()
            ->with(['employee', 'product', 'warehouse', 'issuedBy'])
            ->withSum('returns', 'quantity')
            ->when($request->filled('employee_id'), fn ($query) => $query->where('employee_id', $request->integer('employee_id')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->query('search'));
                $query->whereHas('product', fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%"));
            })
            ->when($request->query('status') === 'outstanding', fn ($query) => $query->whereRaw(
                'COALESCE((SELECT SUM(quantity) FROM employee_asset_returns WHERE employee_asset_assignment_id = employee_asset_assignments.id), 0) < employee_asset_assignments.quantity'
            ))
            ->when($request->query('status') === 'returned', fn ($query) => $query->whereRaw(
                'COALESCE((SELECT SUM(quantity) FROM employee_asset_returns WHERE employee_asset_assignment_id = employee_asset_assignments.id), 0) >= employee_asset_assignments.quantity'
            ))
            ->latest('assigned_at')
            ->latest('id')
            ->paginate($this->perPage($request))
            ->appends($request->query());
        $employees = Employee::query()->orderByRaw("status = 'active' desc")->orderBy('name')->get();

        return view('in_house_use.reports.history', compact('assignments', 'employees'));
    }

    public function store(Request $request, EmployeeAssetService $employeeAssetService)
    {
        if (! $request->has('items')) {
            $request->merge(['items' => [[
                'product_id' => $request->input('product_id'),
                'warehouse_id' => $request->input('warehouse_id'),
                'source_condition' => $request->input('source_condition'),
                'quantity' => $request->input('quantity'),
                'unit_price' => $request->input('unit_price', Product::query()->whereKey($request->input('product_id'))->value('purchase_price') ?? 0),
                'serial_numbers' => $request->input('serial_numbers'),
                'serialless_quantity' => $request->input('serialless_quantity'),
            ]]]);
        }

        $request->merge(['items' => collect($request->input('items', []))->map(function (array $item): array {
            if (! array_key_exists('unit_price', $item) || $item['unit_price'] === '' || $item['unit_price'] === null) {
                $item['unit_price'] = Product::query()->whereKey($item['product_id'] ?? null)->value('purchase_price') ?? 0;
            }

            return $item;
        })->all()]);

        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('status', 'active')],
            'assigned_at' => ['required', 'date'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'items.*.source_condition' => ['required', 'in:new,used'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'items.*.serial_numbers' => ['nullable', 'string'],
            'items.*.serialless_quantity' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $assignments = DB::transaction(function () use ($data, $employeeAssetService, $request): array {
                $employee = Employee::findOrFail($data['employee_id']);
                $assignments = [];

                foreach ($data['items'] as $item) {
                    $assignments[] = $employeeAssetService->assign(
                        $employee,
                        Product::findOrFail($item['product_id']),
                        Warehouse::findOrFail($item['warehouse_id']),
                        [...$item, 'assigned_at' => $data['assigned_at'], 'purpose' => $data['purpose'] ?? null, 'note' => $data['note'] ?? null],
                        $request->user()?->id,
                    );
                }

                return $assignments;
            });
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['items' => $exception->getMessage()]);
        }

        if (count($assignments) === 1) {
            return redirect()->route('in-house-use.show', $assignments[0])->with('success', 'Product assigned to employee successfully.');
        }

        return redirect()->route('in-house-use.report.employees', ['employee_id' => $data['employee_id']])
            ->with('success', count($assignments).' products assigned to employee successfully.');
    }

    public function show(EmployeeAssetAssignment $inHouseUse)
    {
        $inHouseUse->load(['employee', 'product', 'warehouse', 'issuedBy', 'returns.warehouse', 'returns.receivedBy']);
        $inHouseUse->loadSum('returns', 'quantity');
        $warehouses = Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();

        return view('in_house_use.show', ['assignment' => $inHouseUse, 'warehouses' => $warehouses]);
    }

    public function storeReturn(Request $request, EmployeeAssetAssignment $inHouseUse, EmployeeAssetService $employeeAssetService)
    {
        $data = $request->validate([
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'quantity' => ['required', 'integer', 'min:1'],
            'serial_numbers' => ['nullable', 'string'],
            'serialless_quantity' => ['nullable', 'integer', 'min:0'],
            'returned_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $employeeAssetService->returnAsset(
                $inHouseUse,
                Warehouse::findOrFail($data['warehouse_id']),
                $data,
                $request->user()?->id,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return redirect()->route('in-house-use.show', $inHouseUse)->with('success', 'Returned product added to used stock successfully.');
    }

    private function formData(Request $request): array
    {
        $employees = Employee::query()->where('status', 'active')->orderBy('name')->get();
        $warehouses = Warehouse::query()->where('is_active', true)->orderByDesc('is_default')->orderBy('name')->get();
        $products = Product::query()
            ->where('track_inventory', true)
            ->where(fn ($query) => $query->where('stock_quantity', '>', 0)->orWhereHas('usedWarehouseStocks', fn ($query) => $query->where('quantity', '>', 0)))
            ->with([
                'warehouseStocks',
                'usedWarehouseStocks',
                'serials' => fn ($query) => $query->whereIn('status', ['in_stock', 'used_in_stock'])->orderBy('serial_number'),
            ])
            ->orderBy('name')
            ->get();
        $productOptions = $products->map(fn (Product $product): array => [
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'brand' => $product->brand,
            'purchase_price' => (float) $product->purchase_price,
            'sale_price' => (float) $product->sale_price,
            'track_serials' => (bool) $product->track_serial_numbers,
            'new_stocks' => $product->warehouseStocks->pluck('quantity', 'warehouse_id'),
            'used_stocks' => $product->usedWarehouseStocks->pluck('quantity', 'warehouse_id'),
            'new_serials' => $product->serials->where('status', 'in_stock')->groupBy('warehouse_id')->map->pluck('serial_number'),
            'used_serials' => $product->serials->where('status', 'used_in_stock')->groupBy('warehouse_id')->map->pluck('serial_number'),
        ])->values();

        return compact('employees', 'warehouses', 'products', 'productOptions');
    }
}
