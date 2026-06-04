<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductSerial;
use App\Models\User;
use App\Models\WarrantyClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class WarrantyClaimController extends Controller
{
    private const STATUSES = [
        'pending',
        'received',
        'diagnosing',
        'repairing',
        'sent_to_vendor',
        'vendor_returned',
        'ready',
        'delivered',
        'replaced',
        'rejected',
        'paid_service',
        'closed',
    ];

    private const ACTION_TYPES = ['repair', 'replace', 'vendor_return', 'reject', 'paid_service', 'return_only'];

    public function index(Request $request)
    {
        $claims = WarrantyClaim::query()
            ->with(['customer', 'product', 'productSerial', 'vendor'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = trim((string) $request->query('search'));
                $query->where(function ($query) use ($search): void {
                    $query->where('claim_no', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($customer) => $customer->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"))
                        ->orWhereHas('product', fn ($product) => $product->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"))
                        ->orWhereHas('productSerial', fn ($serial) => $serial->where('serial_number', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return view('warranty_claims.index', [
            'claims' => $claims,
            'statuses' => self::STATUSES,
        ]);
    }

    public function create(Request $request)
    {
        $selectedSerial = null;

        if ($request->filled('product_serial_id')) {
            $selectedSerial = ProductSerial::query()
                ->with(['product', 'customer', 'invoice', 'invoiceItem'])
                ->findOrFail($request->integer('product_serial_id'));
        }

        $customers = Customer::query()->where('is_customer', true)->orderBy('name')->get();
        $products = Product::query()->orderBy('name')->get();

        $serials = ProductSerial::query()
            ->with(['product', 'customer', 'invoice'])
            ->where('status', '!=', 'in_stock')
            ->orderBy('serial_number')
            ->limit(1000)
            ->get();

        return view('warranty_claims.create', [
            'selectedSerial' => $selectedSerial,
            'customers' => $customers,
            'products' => $products,
            'customerOptions' => $customers->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'address' => $customer->address,
            ])->values(),
            'productOptions' => $products->map(fn (Product $product): array => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'brand' => $product->brand,
            ])->values(),
            'serialOptions' => $serials->map(fn (ProductSerial $serial): array => [
                'id' => $serial->id,
                'serial_number' => $serial->serial_number,
                'status' => $serial->status,
                'warranty_until' => $serial->warranty_until?->format('Y-m-d'),
                'invoice_no' => $serial->invoice?->invoice_no,
                'product' => $serial->product ? [
                    'id' => $serial->product->id,
                    'name' => $serial->product->name,
                    'sku' => $serial->product->sku,
                    'brand' => $serial->product->brand,
                ] : null,
                'customer' => $serial->customer ? [
                    'id' => $serial->customer->id,
                    'name' => $serial->customer->name,
                    'phone' => $serial->customer->phone,
                    'address' => $serial->customer->address,
                ] : null,
            ])->values(),
            'users' => User::query()->orderBy('name')->get(),
            'vendors' => Customer::query()->where('is_vendor', true)->orderBy('name')->get(),
            'actionTypes' => self::ACTION_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'product_id' => ['nullable', 'exists:products,id'],
            'product_serial_id' => ['nullable', 'exists:product_serials,id'],
            'claim_date' => ['required', 'date'],
            'problem_description' => ['required', 'string'],
            'action_type' => ['required', Rule::in(self::ACTION_TYPES)],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'vendor_id' => ['nullable', Rule::exists('customers', 'id')->where('is_vendor', true)],
        ]);

        try {
            $claim = DB::transaction(function () use ($data): WarrantyClaim {
                $serial = ! empty($data['product_serial_id'])
                    ? ProductSerial::query()->with(['product', 'invoiceItem'])->lockForUpdate()->findOrFail($data['product_serial_id'])
                    : null;

                if ($serial && $serial->status === 'in_stock') {
                    throw new InvalidArgumentException('In-stock serials cannot be claimed as customer warranty.');
                }

                if ($serial && WarrantyClaim::query()
                    ->where('product_serial_id', $serial->id)
                    ->whereIn('status', WarrantyClaim::OPEN_STATUSES)
                    ->exists()) {
                    throw new InvalidArgumentException('This serial already has an open warranty claim.');
                }

                $claim = WarrantyClaim::create([
                    'claim_no' => WarrantyClaim::nextClaimNo(),
                    'customer_id' => (int) $data['customer_id'],
                    'invoice_id' => $serial?->invoice_id,
                    'invoice_item_id' => $serial?->invoice_item_id,
                    'product_id' => $serial?->product_id ?: ($data['product_id'] ?? null),
                    'product_serial_id' => $serial?->id,
                    'claim_date' => $data['claim_date'],
                    'warranty_status' => WarrantyClaim::warrantyStatusFor($serial),
                    'problem_description' => $data['problem_description'],
                    'action_type' => $data['action_type'],
                    'status' => 'pending',
                    'assigned_to' => $data['assigned_to'] ?? null,
                    'vendor_id' => $data['vendor_id'] ?? null,
                    'entry_by' => auth()->user()?->name,
                    'entry_by_type' => auth()->user() ? 'user' : null,
                ]);

                $this->log($claim, null, 'pending', 'Warranty claim created.');

                return $claim;
            });
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['product_serial_id' => $exception->getMessage()]);
        }

        return redirect()->route('warranty-claims.show', $claim)->with('success', 'Warranty claim created.');
    }

    public function show(WarrantyClaim $warrantyClaim)
    {
        $warrantyClaim->load([
            'customer',
            'product',
            'productSerial',
            'invoice',
            'invoiceItem',
            'assignedUser',
            'vendor',
            'replacementProduct',
            'replacementProductSerial',
            'serviceInvoice',
            'logs' => fn ($query) => $query->latest(),
        ]);

        return view('warranty_claims.show', [
            'claim' => $warrantyClaim,
            'statuses' => self::STATUSES,
            'actionTypes' => self::ACTION_TYPES,
            'vendors' => Customer::query()->where('is_vendor', true)->orderBy('name')->get(),
            'replacementSerials' => ProductSerial::query()
                ->with('product')
                ->where('status', 'in_stock')
                ->when($warrantyClaim->product_id, fn ($query) => $query->where('product_id', $warrantyClaim->product_id))
                ->orderBy('serial_number')
                ->limit(200)
                ->get(),
        ]);
    }

    public function updateStatus(Request $request, WarrantyClaim $warrantyClaim)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(self::STATUSES)],
            'action_type' => ['required', Rule::in(self::ACTION_TYPES)],
            'diagnosis_note' => ['nullable', 'string'],
            'resolution_note' => ['nullable', 'string'],
            'delivery_note' => ['nullable', 'string'],
            'vendor_id' => ['nullable', Rule::exists('customers', 'id')->where('is_vendor', true)],
            'note' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($data, $warrantyClaim): void {
            $oldStatus = $warrantyClaim->status;
            $closedAt = in_array($data['status'], ['closed', 'delivered', 'replaced', 'rejected'], true) ? now() : $warrantyClaim->closed_at;

            $warrantyClaim->update([
                'status' => $data['status'],
                'action_type' => $data['action_type'],
                'diagnosis_note' => $data['diagnosis_note'] ?? $warrantyClaim->diagnosis_note,
                'resolution_note' => $data['resolution_note'] ?? $warrantyClaim->resolution_note,
                'delivery_note' => $data['delivery_note'] ?? $warrantyClaim->delivery_note,
                'vendor_id' => $data['vendor_id'] ?? $warrantyClaim->vendor_id,
                'received_at' => $data['status'] === 'received' && ! $warrantyClaim->received_at ? now() : $warrantyClaim->received_at,
                'closed_at' => $closedAt,
            ]);

            $this->log($warrantyClaim, $oldStatus, $data['status'], $data['note'] ?? null);
        });

        return back()->with('success', 'Warranty claim updated.');
    }

    public function replace(Request $request, WarrantyClaim $warrantyClaim)
    {
        $data = $request->validate([
            'replacement_product_serial_id' => ['required', 'exists:product_serials,id'],
            'resolution_note' => ['nullable', 'string'],
        ]);

        try {
            DB::transaction(function () use ($data, $warrantyClaim): void {
                $warrantyClaim->loadMissing(['productSerial', 'customer']);
                $replacement = ProductSerial::query()->lockForUpdate()->findOrFail($data['replacement_product_serial_id']);

                if ($replacement->status !== 'in_stock') {
                    throw new InvalidArgumentException('Replacement serial is not available in stock.');
                }

                if ($warrantyClaim->product_id && $replacement->product_id !== $warrantyClaim->product_id) {
                    throw new InvalidArgumentException('Replacement serial must belong to the same product.');
                }

                $oldStatus = $warrantyClaim->status;

                if ($warrantyClaim->productSerial) {
                    $warrantyClaim->productSerial->update([
                        'status' => 'replaced',
                        'note' => 'Replaced via warranty claim '.$warrantyClaim->claim_no,
                    ]);
                }

                $replacement->update([
                    'status' => 'sold',
                    'customer_id' => $warrantyClaim->customer_id,
                    'invoice_id' => $warrantyClaim->invoice_id,
                    'invoice_item_id' => $warrantyClaim->invoice_item_id,
                    'sold_at' => now(),
                    'warranty_until' => $warrantyClaim->productSerial?->warranty_until,
                    'note' => 'Replacement via warranty claim '.$warrantyClaim->claim_no,
                ]);
                $replacement->product()->where('track_inventory', true)->where('stock_quantity', '>', 0)->decrement('stock_quantity');

                $warrantyClaim->update([
                    'status' => 'replaced',
                    'action_type' => 'replace',
                    'replacement_product_id' => $replacement->product_id,
                    'replacement_product_serial_id' => $replacement->id,
                    'resolution_note' => $data['resolution_note'] ?? $warrantyClaim->resolution_note,
                    'closed_at' => now(),
                ]);

                $this->log($warrantyClaim, $oldStatus, 'replaced', 'Replacement serial '.$replacement->serial_number.' assigned.');
            });
        } catch (InvalidArgumentException $exception) {
            return back()->withErrors(['replacement_product_serial_id' => $exception->getMessage()]);
        }

        return back()->with('success', 'Replacement completed.');
    }

    public function createServiceInvoice(Request $request, WarrantyClaim $warrantyClaim)
    {
        $data = $request->validate([
            'service_charge' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $invoice = DB::transaction(function () use ($data, $warrantyClaim): Invoice {
            $invoice = Invoice::create([
                'customer_id' => $warrantyClaim->customer_id,
                'invoice_no' => Invoice::generateInvoiceNo($warrantyClaim->customer_id, now()->format('Y-m')),
                'billing_month' => now()->format('Y-m'),
                'invoice_type' => 'product',
                'subtotal' => $data['service_charge'],
                'discount' => 0,
                'vat' => 0,
                'total' => $data['service_charge'],
                'paid_amount' => 0,
                'due_amount' => $data['service_charge'],
                'status' => ((float) $data['service_charge']) <= 0 ? 'paid' : 'unpaid',
            ]);

            $invoice->items()->create([
                'product_id' => null,
                'product_name' => 'Paid warranty service - '.$warrantyClaim->claim_no,
                'product_type' => 'warranty',
                'quantity' => 1,
                'unit_price' => $data['service_charge'],
                'total' => $data['service_charge'],
                'service_note' => $data['note'] ?? null,
            ]);

            $oldStatus = $warrantyClaim->status;
            $warrantyClaim->update([
                'status' => 'paid_service',
                'action_type' => 'paid_service',
                'service_invoice_id' => $invoice->id,
                'service_charge' => $data['service_charge'],
            ]);
            $this->log($warrantyClaim, $oldStatus, 'paid_service', 'Paid service invoice '.$invoice->invoice_no.' created.');

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)->with('success', 'Paid service invoice created from warranty claim.');
    }

    private function log(WarrantyClaim $claim, ?string $oldStatus, string $newStatus, ?string $note = null): void
    {
        $claim->logs()->create([
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => $note,
            'entry_by' => auth()->user()?->name,
            'entry_by_type' => auth()->user() ? 'user' : null,
        ]);
    }
}
