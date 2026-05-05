<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::query()
            ->with('customer')
            ->when($request->status, fn ($query, string $status) => $query->where('status', $status))
            ->when($request->billing_month, fn ($query, string $month) => $query->where('billing_month', $month))
            ->latest()
            ->paginate(10);

        return view('invoices.index', compact('invoices'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        return view('invoices.create', compact('customers'));
    }

    public function searchCustomers(Request $request)
    {
        $search = trim((string) $request->query('q'));

        return Customer::query()
            ->where('status', 'active')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('connection_id', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->limit(10)
            ->get(['id', 'name', 'phone', 'connection_id']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'customer_name' => ['required_without:customer_id', 'nullable', 'string', 'max:255'],
            'customer_phone' => ['required_without:customer_id', 'nullable', 'string', 'max:30'],
            'billing_month' => ['required', 'date_format:Y-m'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'discount' => ['required', 'numeric', 'min:0'],
            'vat' => ['required', 'numeric', 'min:0'],
            'due_date' => ['nullable', 'date'],
        ]);

        $customerId = $data['customer_id'] ?? null;

        if (! $customerId) {
            $customer = Customer::where('phone', $data['customer_phone'])->first();

            if (! $customer) {
                $customer = Customer::create([
                    'name' => $data['customer_name'],
                    'phone' => $data['customer_phone'],
                    'connection_id' => $this->generateCustomerConnectionId(),
                    'address' => 'Not provided',
                    'status' => 'active',
                ]);
            }

            $customerId = $customer->id;
        }

        $subtotal = 0;
        $itemsData = [];

        foreach ($data['items'] as $item) {
            $total = $item['quantity'] * $item['unit_price'];
            $subtotal += $total;
            $itemsData[] = [
                'product_name' => $item['product_name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $total,
            ];
        }

        $total = $subtotal - $data['discount'] + $data['vat'];

        $invoice = Invoice::create([
            'customer_id' => $customerId,
            'invoice_no' => Invoice::generateInvoiceNo($customerId, $data['billing_month']),
            'billing_month' => $data['billing_month'],
            'invoice_type' => 'product',
            'subtotal' => $subtotal,
            'discount' => $data['discount'],
            'vat' => $data['vat'],
            'total' => $total,
            'paid_amount' => 0,
            'due_amount' => max(0, $total),
            'status' => $total <= 0 ? 'paid' : 'unpaid',
            'due_date' => $data['due_date'] ?? null,
        ]);

        foreach ($itemsData as $itemData) {
            $invoice->items()->create($itemData);
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created successfully.');
    }

    private function generateCustomerConnectionId(): string
    {
        do {
            $connectionId = 'AUTO-'.now()->format('YmdHis').'-'.random_int(100, 999);
        } while (Customer::where('connection_id', $connectionId)->exists());

        return $connectionId;
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer', 'payments.account', 'items']);

        return view('invoices.show', compact('invoice'));
    }

    public function challan(Invoice $invoice)
    {
        $invoice->load(['customer', 'items']);

        return view('invoices.challan', compact('invoice'));
    }

    public function quotation(Invoice $invoice)
    {
        $invoice->load(['customer', 'items']);

        return view('invoices.quotation', compact('invoice'));
    }

    public function deliveryChallan(Invoice $invoice)
    {
        $invoice->load(['customer', 'items']);

        return view('invoices.delivery_challan', compact('invoice'));
    }

    public function generate(Request $request, BillingService $billingService)
    {
        $data = $request->validate([
            'billing_month' => ['required', 'date_format:Y-m'],
        ]);

        $activeSubscriptions = Subscription::query()
            ->where('status', 'active')
            ->whereHas('customer', fn ($query) => $query->where('status', 'active'))
            ->count();

        if ($activeSubscriptions === 0) {
            return back()->withErrors([
                'billing_month' => 'No active customer subscription found. Add an active package to a customer first, then generate bills.',
            ]);
        }

        $created = $billingService->generateMonthlyBills($data['billing_month']);

        if ($created->isEmpty()) {
            return redirect()
                ->route('invoices.index', ['billing_month' => $data['billing_month']])
                ->with('success', 'Bills for '.$data['billing_month'].' are already generated.');
        }

        return redirect()
            ->route('invoices.index', ['billing_month' => $data['billing_month']])
            ->with('success', $created->count().' invoice(s) generated.');
    }
}
