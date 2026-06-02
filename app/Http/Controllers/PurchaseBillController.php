<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSerial;
use App\Models\PurchaseBill;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class PurchaseBillController extends Controller
{
    public function index(Request $request)
    {
        return view('purchase_bills.index', [
            'purchaseBills' => PurchaseBill::query()
                ->with('party')
                ->latest('purchase_date')
                ->latest()
                ->paginate($this->perPage($request))
                ->appends($request->query()),
        ]);
    }

    public function create()
    {
        return view('purchase_bills.create', [
            'vendors' => Customer::query()->where('is_vendor', true)->orderBy('name')->get(),
            'products' => Product::query()->with('productCategory.parent.parent.parent')->orderBy('name')->get(),
            'brands' => Product::query()->whereNotNull('brand')->where('brand', '!=', '')->distinct()->orderBy('brand')->pluck('brand'),
            'categoryTree' => ProductCategory::query()->with('children.children.children.children')->whereNull('parent_id')->orderBy('name')->get(),
            'defaultBillNo' => $this->nextBillNo(),
        ]);
    }

    public function store(Request $request, InventoryService $inventoryService)
    {
        $data = $request->validate([
            'party_id' => ['nullable', Rule::exists('customers', 'id')->where('is_vendor', true)],
            'bill_no' => ['required', 'string', 'max:100', 'unique:purchase_bills,bill_no'],
            'purchase_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'items.*.serial_numbers' => ['nullable', 'string'],
        ]);

        $items = collect($data['items'])
            ->filter(fn (array $item): bool => ! empty($item['product_id']) && (int) ($item['quantity'] ?? 0) > 0)
            ->values();

        if ($items->isEmpty()) {
            return back()->withInput()->withErrors(['items' => 'At least one product line is required.']);
        }

        try {
            DB::transaction(function () use ($data, $items, $inventoryService): void {
                $purchaseBill = PurchaseBill::create([
                    'party_id' => $data['party_id'] ?? null,
                    'bill_no' => $data['bill_no'],
                    'purchase_date' => $data['purchase_date'],
                    'subtotal' => $items->sum(fn (array $item): float => (float) $item['quantity'] * (float) $item['unit_price']),
                    'note' => $data['note'] ?? null,
                ]);

                foreach ($items as $item) {
                    $quantity = (int) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];
                    $warrantyMonths = isset($item['warranty_months']) && $item['warranty_months'] !== ''
                        ? (int) $item['warranty_months']
                        : null;
                    $serialNumbers = $this->serialNumbers($item['serial_numbers'] ?? '');

                    if ($serialNumbers !== [] && count($serialNumbers) > $quantity) {
                        throw new InvalidArgumentException('Serial number count cannot be greater than purchased quantity.');
                    }

                    $billItem = $purchaseBill->items()->create([
                        'product_id' => $item['product_id'],
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total' => $quantity * $unitPrice,
                        'warranty_months' => $warrantyMonths,
                    ]);

                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                    if (! $product->track_inventory && $serialNumbers !== []) {
                        throw new InvalidArgumentException('Serial numbers can only be added for stock-tracked products.');
                    }

                    if ($product->track_inventory) {
                        $inventoryService->moveStock($product, 'in', $quantity, 'Purchase bill '.$purchaseBill->bill_no, $purchaseBill->bill_no);
                    }

                    foreach ($serialNumbers as $serialNumber) {
                        ProductSerial::create([
                            'product_id' => $product->id,
                            'purchase_bill_id' => $purchaseBill->id,
                            'purchase_bill_item_id' => $billItem->id,
                            'serial_number' => $serialNumber,
                            'warranty_until' => $warrantyMonths ? Carbon::parse($data['purchase_date'])->addMonths($warrantyMonths)->toDateString() : null,
                            'status' => 'in_stock',
                        ]);
                    }
                }
            });
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['items' => $exception->getMessage()]);
        }

        return redirect()->route('purchase-bills.index')->with('success', 'Purchase bill saved and stock updated.');
    }

    public function show(PurchaseBill $purchaseBill)
    {
        $purchaseBill->load(['party', 'items.product', 'items.serials']);

        return view('purchase_bills.show', compact('purchaseBill'));
    }

    private function serialNumbers(string $serialNumbers): array
    {
        return collect(preg_split('/\R/', $serialNumbers) ?: [])
            ->map(fn (string $serial): string => trim($serial))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function nextBillNo(): string
    {
        $prefix = 'PB-'.now()->format('Y-m-');
        $count = PurchaseBill::query()->where('bill_no', 'like', $prefix.'%')->count() + 1;

        return $prefix.str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }
}
