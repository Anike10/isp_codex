<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductSerial;
use App\Models\PurchaseBill;
use App\Services\InventoryService;
use App\Support\SerialNumberParser;
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
        $products = Product::query()->with('productCategory.parent.parent.parent')->orderBy('name')->get();

        return view('purchase_bills.create', [
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
            'defaultBillNo' => $this->nextBillNo(),
        ]);
    }

    public function store(Request $request, InventoryService $inventoryService)
    {
        $data = $request->validate([
            'party_id' => ['nullable', Rule::exists('customers', 'id')->where('is_vendor', true)],
            'party_name' => ['nullable', 'string', 'max:255'],
            'bill_no' => ['required', 'string', 'max:100', 'unique:purchase_bills,bill_no'],
            'purchase_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'exists:products,id'],
            'items.*.product_name' => ['required_without:items.*.product_id', 'nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.warranty_months' => ['nullable', 'integer', 'min:0', 'max:120'],
            'items.*.warranty_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'items.*.serial_numbers' => ['nullable', 'string'],
        ]);

        $items = collect($data['items'])
            ->filter(fn (array $item): bool => (! empty($item['product_id']) || trim((string) ($item['product_name'] ?? '')) !== '') && (int) ($item['quantity'] ?? 0) > 0)
            ->values();

        if ($items->isEmpty()) {
            return back()->withInput()->withErrors(['items' => 'At least one product line is required.']);
        }

        try {
            DB::transaction(function () use ($data, $items, $inventoryService): void {
                $partyId = $this->resolveVendorPartyId($data);

                $purchaseBill = PurchaseBill::create([
                    'party_id' => $partyId,
                    'bill_no' => $data['bill_no'],
                    'purchase_date' => $data['purchase_date'],
                    'subtotal' => $items->sum(fn (array $item): float => (float) $item['quantity'] * (float) $item['unit_price']),
                    'note' => $data['note'] ?? null,
                ]);

                foreach ($items as $item) {
                    $quantity = (int) $item['quantity'];
                    $unitPrice = (float) $item['unit_price'];
                    $serialNumbers = app(SerialNumberParser::class)->parse($item['serial_numbers'] ?? '');
                    $product = $this->resolvePurchaseProduct($item, $serialNumbers);
                    $warrantyMonths = isset($item['warranty_months']) && $item['warranty_months'] !== ''
                        ? (int) $item['warranty_months']
                        : null;
                    $warrantyDays = $this->warrantyDays($item, $product, $warrantyMonths);

                    if ($serialNumbers !== [] && count($serialNumbers) > $quantity) {
                        throw new InvalidArgumentException('Serial number count cannot be greater than purchased quantity.');
                    }

                    if (! $product->track_serial_numbers && $serialNumbers !== []) {
                        throw new InvalidArgumentException('Serial numbers can only be added for serial-tracked products.');
                    }

                    $billItem = $purchaseBill->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'total' => $quantity * $unitPrice,
                        'warranty_months' => $warrantyMonths,
                        'warranty_days' => $warrantyDays,
                    ]);

                    if ($product->track_inventory) {
                        $inventoryService->moveStock($product, 'in', $quantity, 'Purchase bill '.$purchaseBill->bill_no, $purchaseBill->bill_no);
                    }

                    foreach ($serialNumbers as $serialNumber) {
                        ProductSerial::create([
                            'product_id' => $product->id,
                            'purchase_bill_id' => $purchaseBill->id,
                            'purchase_bill_item_id' => $billItem->id,
                            'serial_number' => $serialNumber,
                            'warranty_until' => $warrantyDays ? Carbon::parse($data['purchase_date'])->addDays($warrantyDays)->toDateString() : null,
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

    private function resolvePurchaseProduct(array $item, array $serialNumbers): Product
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

        return Product::create([
            'name' => $name,
            'sku' => $this->nextProductSku($name),
            'track_inventory' => true,
            'track_serial_numbers' => $serialNumbers !== [],
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
}
