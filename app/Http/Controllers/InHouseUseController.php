<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeAssetAssignment;
use App\Models\Product;
use App\Models\UsedProductWarehouseStock;
use App\Models\Warehouse;
use App\Services\EmployeeAssetService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class InHouseUseController extends Controller
{
    public function index(Request $request)
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
        $employeeSummaries = Employee::query()
            ->whereHas('assetAssignments')
            ->with(['assetAssignments.product', 'assetAssignments.returns'])
            ->orderBy('name')
            ->get();
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
            'track_serials' => (bool) $product->track_serial_numbers,
            'new_stocks' => $product->warehouseStocks->pluck('quantity', 'warehouse_id'),
            'used_stocks' => $product->usedWarehouseStocks->pluck('quantity', 'warehouse_id'),
            'new_serials' => $product->serials->where('status', 'in_stock')->groupBy('warehouse_id')->map->pluck('serial_number'),
            'used_serials' => $product->serials->where('status', 'used_in_stock')->groupBy('warehouse_id')->map->pluck('serial_number'),
        ])->values();
        $usedStocks = UsedProductWarehouseStock::query()
            ->where('quantity', '>', 0)
            ->with(['product', 'warehouse'])
            ->orderByDesc('quantity')
            ->get();

        return view('in_house_use.index', compact(
            'assignments',
            'employees',
            'employeeSummaries',
            'warehouses',
            'products',
            'productOptions',
            'usedStocks',
        ));
    }

    public function store(Request $request, EmployeeAssetService $employeeAssetService)
    {
        $data = $request->validate([
            'employee_id' => ['required', Rule::exists('employees', 'id')->where('status', 'active')],
            'product_id' => ['required', 'exists:products,id'],
            'warehouse_id' => ['required', Rule::exists('warehouses', 'id')->where('is_active', true)],
            'source_condition' => ['required', 'in:new,used'],
            'quantity' => ['required', 'integer', 'min:1'],
            'serial_numbers' => ['nullable', 'string'],
            'serialless_quantity' => ['nullable', 'integer', 'min:0'],
            'assigned_at' => ['required', 'date'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $assignment = $employeeAssetService->assign(
                Employee::findOrFail($data['employee_id']),
                Product::findOrFail($data['product_id']),
                Warehouse::findOrFail($data['warehouse_id']),
                $data,
                $request->user()?->id,
            );
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['quantity' => $exception->getMessage()]);
        }

        return redirect()->route('in-house-use.show', $assignment)->with('success', 'Product assigned to employee successfully.');
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
}
