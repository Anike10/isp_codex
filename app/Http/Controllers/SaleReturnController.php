<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerBalanceTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\SaleReturn;
use App\Services\InventoryService;
use App\Support\SerialNumberParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class SaleReturnController extends Controller
{
    public function index(Request $request)
    {
        $query = SaleReturn::query()
            ->with(['invoice', 'customer'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->query('search'));
                $query->where(function ($query) use ($search): void {
                    $query->where('return_no', 'like', "%{$search}%")
                        ->orWhere('note', 'like', "%{$search}%")
                        ->orWhereHas('invoice', fn ($query) => $query->where('invoice_no', 'like', "%{$search}%"))
                        ->orWhereHas('customer', fn ($query) => $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('connection_id', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('customer_id'), fn ($query) => $query->where('customer_id', $request->integer('customer_id')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('return_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('return_date', '<=', $request->date('to')))
            ->latest('return_date')
            ->latest();

        return view('sale_returns.index', [
            'saleReturns' => $query->paginate($this->perPage($request))->appends($request->query()),
            'customers' => Customer::query()->where('is_customer', true)->orderBy('name')->get(['id', 'name', 'phone']),
        ]);
    }

    public function create(Request $request)
    {
        $invoice = null;
        if ($request->filled('invoice_id')) {
            $invoice = Invoice::query()
                ->with(['customer', 'items.product', 'items.saleReturnItems'])
                ->findOrFail($request->integer('invoice_id'));
        }

        return view('sale_returns.create', [
            'invoice' => $invoice,
            'invoices' => Invoice::query()
                ->with('customer')
                ->where('invoice_type', 'product')
                ->latest()
                ->limit(100)
                ->get(),
            'returnNo' => SaleReturn::generateReturnNo(),
            'returnable' => $invoice ? $this->returnableItems($invoice) : collect(),
        ]);
    }

    public function store(Request $request, InventoryService $inventoryService)
    {
        $data = $request->validate([
            'invoice_id' => ['required', 'exists:invoices,id'],
            'return_no' => ['required', 'string', 'max:100', 'unique:sale_returns,return_no'],
            'return_date' => ['required', 'date'],
            'note' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.invoice_item_id' => ['required', Rule::exists('invoice_items', 'id')->where('invoice_id', $request->integer('invoice_id'))],
            'items.*.quantity' => ['nullable', 'integer', 'min:0'],
            'items.*.serial_numbers' => ['nullable', 'string'],
            'items.*.serialless_quantity' => ['nullable', 'integer', 'min:0'],
        ]);

        try {
            $saleReturn = DB::transaction(function () use ($data, $inventoryService): SaleReturn {
                $invoice = Invoice::query()
                    ->with('items')
                    ->whereKey($data['invoice_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $saleReturn = SaleReturn::create([
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'return_no' => $data['return_no'],
                    'return_date' => $data['return_date'],
                    'subtotal' => 0,
                    'credit_total' => 0,
                    'invoice_credit_amount' => 0,
                    'advance_credit_amount' => 0,
                    'note' => $data['note'] ?? null,
                ]);

                $subtotal = 0.0;
                $createdItems = 0;

                foreach ($data['items'] as $row) {
                    $invoiceItem = InvoiceItem::query()
                        ->with('product')
                        ->whereKey($row['invoice_item_id'])
                        ->where('invoice_id', $invoice->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $product = $invoiceItem->product_id ? Product::query()->lockForUpdate()->find($invoiceItem->product_id) : null;
                    $serialNumbers = app(SerialNumberParser::class)->parse($row['serial_numbers'] ?? '');
                    $seriallessQuantity = (int) ($row['serialless_quantity'] ?? 0);
                    $quantity = (int) ($row['quantity'] ?? 0);

                    if ($product?->track_serial_numbers) {
                        $quantity = count($serialNumbers) + $seriallessQuantity;
                    }

                    if ($quantity <= 0) {
                        continue;
                    }

                    $this->validateReturnQuantity($invoiceItem, $quantity, $serialNumbers, $seriallessQuantity);

                    $lineTotal = round($quantity * (float) $invoiceItem->unit_price, 2);
                    $subtotal += $lineTotal;

                    $saleReturn->items()->create([
                        'invoice_item_id' => $invoiceItem->id,
                        'product_id' => $invoiceItem->product_id,
                        'product_name' => $invoiceItem->product_name,
                        'quantity' => $quantity,
                        'serialless_quantity' => $seriallessQuantity,
                        'unit_price' => $invoiceItem->unit_price,
                        'total' => $lineTotal,
                        'serial_numbers' => $serialNumbers === [] ? null : implode(', ', $serialNumbers),
                    ]);
                    $createdItems++;

                    $this->restoreReturnedInventory($saleReturn, $invoiceItem, $product, $quantity, $serialNumbers, $seriallessQuantity, $inventoryService);
                }

                if ($createdItems === 0) {
                    throw new InvalidArgumentException('Enter at least one return quantity.');
                }

                $creditTotal = $this->calculateReturnCredit($invoice, $saleReturn, $subtotal);
                $saleReturn->update([
                    'subtotal' => $subtotal,
                    'credit_total' => $creditTotal,
                ]);
                $this->applyReturnCredit($invoice, $saleReturn, $creditTotal, $data['return_date']);

                return $saleReturn;
            });
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['items' => $exception->getMessage()]);
        }

        return redirect()->route('sale-returns.show', $saleReturn)->with('success', 'Sale return saved, stock restored, and credit applied to the source invoice.');
    }

    public function show(SaleReturn $saleReturn)
    {
        $saleReturn->load(['invoice', 'customer', 'items.product']);

        return view('sale_returns.show', compact('saleReturn'));
    }

    private function returnableItems(Invoice $invoice)
    {
        $defaultWarehouseId = app(InventoryService::class)->defaultWarehouse()->id;

        return $invoice->items->map(function (InvoiceItem $item) use ($defaultWarehouseId): array {
            $returnedQuantity = (int) $item->saleReturnItems->sum('quantity');
            $returnedSeriallessQuantity = (int) $item->saleReturnItems->sum('serialless_quantity');
            $soldSerials = app(SerialNumberParser::class)->parse($item->serial_numbers ?? '');
            $returnedSerials = $item->saleReturnItems
                ->pluck('serial_numbers')
                ->filter()
                ->flatMap(fn (string $serials): array => app(SerialNumberParser::class)->parse($serials))
                ->values()
                ->all();
            $availableSerials = array_values(array_diff($soldSerials, $returnedSerials));

            return [
                'item' => $item,
                'remaining_quantity' => max(0, (int) $item->quantity - $returnedQuantity),
                'remaining_serialless_quantity' => max(0, (int) $item->serialless_quantity - $returnedSeriallessQuantity),
                'available_serials' => ProductSerial::query()
                    ->where('product_id', $item->product_id)
                    ->whereIn('serial_number', $availableSerials)
                    ->where('status', 'sold')
                    ->where('invoice_item_id', $item->id)
                    ->orderBy('serial_number')
                    ->pluck('serial_number')
                    ->all(),
                'default_warehouse_id' => $defaultWarehouseId,
            ];
        })->filter(fn (array $row): bool => $row['remaining_quantity'] > 0)->values();
    }

    private function validateReturnQuantity(InvoiceItem $invoiceItem, int $quantity, array $serialNumbers, int $seriallessQuantity): void
    {
        $returnedQuantity = (int) $invoiceItem->saleReturnItems()->sum('quantity');
        $remainingQuantity = (int) $invoiceItem->quantity - $returnedQuantity;

        if ($quantity > $remainingQuantity) {
            throw new InvalidArgumentException('Return quantity cannot be greater than remaining sold quantity for '.$invoiceItem->product_name.'.');
        }

        $product = $invoiceItem->product;
        if (! $product?->track_serial_numbers) {
            if ($serialNumbers !== [] || $seriallessQuantity > 0) {
                throw new InvalidArgumentException('Serial and Serial-less Qty are only for serial-tracked sale items.');
            }

            return;
        }

        if (count($serialNumbers) + $seriallessQuantity !== $quantity) {
            throw new InvalidArgumentException('For serial-tracked returns, serial count plus Serial-less Qty must match return quantity.');
        }

        $returnedSerialless = (int) $invoiceItem->saleReturnItems()->sum('serialless_quantity');
        if ($seriallessQuantity > ((int) $invoiceItem->serialless_quantity - $returnedSerialless)) {
            throw new InvalidArgumentException('Serial-less return quantity exceeds remaining serial-less sold quantity for '.$invoiceItem->product_name.'.');
        }

        if ($serialNumbers === []) {
            return;
        }

        $soldSerials = ProductSerial::query()
            ->where('product_id', $product->id)
            ->where('invoice_item_id', $invoiceItem->id)
            ->where('status', 'sold')
            ->whereIn('serial_number', $serialNumbers)
            ->lockForUpdate()
            ->pluck('serial_number')
            ->all();
        $missing = array_values(array_diff($serialNumbers, $soldSerials));

        if ($missing !== []) {
            throw new InvalidArgumentException('These serials are not sold on this invoice or already returned: '.implode(', ', $missing));
        }
    }

    private function restoreReturnedInventory(SaleReturn $saleReturn, InvoiceItem $invoiceItem, ?Product $product, int $quantity, array $serialNumbers, int $seriallessQuantity, InventoryService $inventoryService): void
    {
        if (! $product?->track_inventory) {
            return;
        }

        $inventoryService->moveStock($product, 'in', $quantity, 'Sale return '.$saleReturn->return_no, $saleReturn->return_no, $seriallessQuantity, null, $serialNumbers);

        if ($serialNumbers === []) {
            return;
        }

        ProductSerial::query()
            ->where('product_id', $product->id)
            ->where('invoice_item_id', $invoiceItem->id)
            ->whereIn('serial_number', $serialNumbers)
            ->lockForUpdate()
            ->get()
            ->each(fn (ProductSerial $serial) => $serial->update([
                'status' => 'in_stock',
                'warehouse_id' => $inventoryService->defaultWarehouse()->id,
                'customer_id' => null,
                'invoice_id' => null,
                'invoice_item_id' => null,
                'sold_at' => null,
                'note' => 'Returned via '.$saleReturn->return_no,
            ]));
    }

    private function applyReturnCredit(Invoice $invoice, SaleReturn $saleReturn, float $amount, string $returnDate): void
    {
        if ($amount <= 0) {
            return;
        }

        $previousReturnCredit = (float) $invoice->saleReturns()
            ->whereKeyNot($saleReturn->id)
            ->sum('credit_total');
        $invoiceDue = round(max(0, (float) $invoice->total - (float) $invoice->paid_amount - $previousReturnCredit), 2);
        $invoiceCredit = round(min($amount, $invoiceDue), 2);
        $advanceCredit = round(max(0, $amount - $invoiceCredit), 2);

        $saleReturn->update([
            'invoice_credit_amount' => $invoiceCredit,
            'advance_credit_amount' => $advanceCredit,
        ]);
        $invoice->recalculateSettlement();

        if ($advanceCredit <= 0) {
            return;
        }

        $customer = Customer::query()->whereKey($invoice->customer_id)->lockForUpdate()->firstOrFail();
        $balanceAfter = round((float) $customer->account_balance + $advanceCredit, 2);

        CustomerBalanceTransaction::create([
            'customer_id' => $customer->id,
            'payment_id' => null,
            'payment_account_id' => null,
            'payment_method' => 'sale_return',
            'direction' => 'credit',
            'amount' => $advanceCredit,
            'balance_after' => $balanceAfter,
            'transaction_date' => $returnDate,
            'reference' => $saleReturn->return_no,
            'note' => 'Sale return excess credit after settling invoice '.$invoice->invoice_no.'.',
        ]);

        $customer->update(['account_balance' => $balanceAfter]);
    }

    private function calculateReturnCredit(Invoice $invoice, SaleReturn $saleReturn, float $grossReturn): float
    {
        $previousReturns = $invoice->saleReturns()->whereKeyNot($saleReturn->id);
        $previousGross = (float) (clone $previousReturns)->sum('subtotal');
        $previousCredit = (float) (clone $previousReturns)->sum('credit_total');
        $remainingCredit = round(max(0, (float) $invoice->total - $previousCredit), 2);
        $invoiceGross = (float) $invoice->subtotal;

        if ($remainingCredit <= 0 || $grossReturn <= 0) {
            return 0;
        }

        if ($invoiceGross <= 0 || $previousGross + $grossReturn >= $invoiceGross - 0.005) {
            return $remainingCredit;
        }

        return round(min($remainingCredit, $grossReturn * (float) $invoice->total / $invoiceGross), 2);
    }
}
