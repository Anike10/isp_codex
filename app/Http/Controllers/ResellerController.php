<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use InvalidArgumentException;

class ResellerController extends Controller
{
    public function index(Request $request)
    {
        $resellers = Customer::query()
            ->where('is_reseller', true)
            ->withCount('resellerCustomers')
            ->addSelect(['assigned_due_amount' => Invoice::query()
                ->selectRaw('COALESCE(SUM(invoices.due_amount), 0)')
                ->join('customers as assigned_customers', 'assigned_customers.id', '=', 'invoices.customer_id')
                ->whereColumn('assigned_customers.reseller_id', 'customers.id')
                ->where('invoices.due_amount', '>', 0)])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->trim()->toString();
                $query->where(fn ($query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"));
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->latest()
            ->paginate($this->perPage($request))
            ->appends($request->query());

        return view('resellers.index', compact('resellers'));
    }

    public function show(Customer $reseller)
    {
        abort_unless($reseller->is_reseller, 404);

        return app(ResellerPortalController::class)->dashboardFor($reseller, true);
    }

    public function pay(Request $request, Customer $reseller, Invoice $invoice, PaymentService $paymentService)
    {
        abort_unless($reseller->is_reseller, 404);
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'operation_key' => ['required', 'uuid'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['payment_date'] = now()->toDateString();
        $data['entry_by'] = $request->user()->id;

        try {
            $paymentService->applyResellerWalletToInvoice($reseller, $invoice, $data);
        } catch (InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['amount' => $exception->getMessage()]);
        }

        return redirect()->route('resellers.show', $reseller)->with('success', 'Invoice payment completed from reseller wallet.');
    }
}
