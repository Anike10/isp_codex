<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\PaymentAccount;
use App\Services\BulkCustomerPaymentService;
use App\Services\PaymentAccountPreferenceService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Throwable;

class BulkCustomerPaymentController extends Controller
{
    public function select(Request $request)
    {
        $data = $request->validate([
            'customer_ids' => ['required', 'array', 'min:1', 'max:2000'],
            'customer_ids.*' => ['required', 'integer', 'distinct', 'exists:customers,id'],
        ]);

        $token = bin2hex(random_bytes(20));
        $request->session()->put($this->selectionKey($token), array_values($data['customer_ids']));

        return redirect()->route('customers.bulk-payments.create', $token);
    }

    public function create(
        Request $request,
        string $token,
        BulkCustomerPaymentService $bulkPaymentService,
        PaymentAccountPreferenceService $preferenceService,
    ) {
        $customerIds = $this->selectedCustomerIds($request, $token);
        $customers = Customer::query()
            ->with(['activeSubscription.package', 'latestSubscription.package'])
            ->whereKey($customerIds)
            ->orderBy('name')
            ->orderBy('id')
            ->get();
        $durationOptions = $bulkPaymentService->durationOptions();

        $rows = $customers->map(function (Customer $customer) use ($bulkPaymentService, $durationOptions): array {
            $subscription = $customer->activeSubscription ?: $customer->latestSubscription;
            $package = $subscription?->package;
            $effectivePrice = $subscription ? $subscription->effectivePrice() : 0.0;
            $amounts = collect(array_keys($durationOptions))->mapWithKeys(
                fn (string $duration): array => [$duration => $package
                    ? $bulkPaymentService->amountForPrice($effectivePrice, $duration)
                    : 0]
            )->all();

            return [
                'customer' => $customer,
                'package' => $package,
                'amounts' => $amounts,
                'payable' => $package && $effectivePrice > 0,
            ];
        });

        return view('customers.bulk-payment', [
            'token' => $token,
            'rows' => $rows,
            'durationOptions' => $durationOptions,
            'paymentAccounts' => PaymentAccount::query()
                ->where('status', 'active')
                ->usableBy($request->user())
                ->orderBy('payment_method')
                ->orderBy('account_name')
                ->get(),
            'paymentDefault' => $preferenceService->forUser($request->user()),
        ]);
    }

    public function store(
        Request $request,
        string $token,
        BulkCustomerPaymentService $bulkPaymentService,
        PaymentAccountPreferenceService $preferenceService,
    ) {
        $customerIds = $this->selectedCustomerIds($request, $token);
        $data = $request->validate([
            'duration' => ['required', Rule::in(array_keys($bulkPaymentService->durationOptions()))],
            'payment_method' => ['required', 'in:cash,bkash,nagad,bank'],
            'payment_account_id' => ['nullable', 'integer', 'exists:payment_accounts,id'],
            'payment_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'set_as_default' => ['nullable', 'boolean'],
        ]);
        $rememberAsDefault = $request->boolean('set_as_default');
        unset($data['set_as_default']);

        if ($data['payment_method'] === 'cash') {
            $data['payment_account_id'] = null;
        } else {
            $account = PaymentAccount::query()
                ->whereKey($data['payment_account_id'] ?? null)
                ->where('payment_method', $data['payment_method'])
                ->where('status', 'active')
                ->usableBy($request->user())
                ->first();

            if (! $account) {
                return back()->withInput()->withErrors([
                    'payment_account_id' => 'Please select a valid active account for this payment method.',
                ]);
            }
        }

        try {
            $result = $bulkPaymentService->record(
                $customerIds,
                $data,
                $request->user()?->id,
                $token,
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'bulk_payment' => 'Bulk payment failed ('.class_basename($exception).'): '.$exception->getMessage(),
            ]);
        }

        $preferenceService->remember(
            $request->user(),
            $rememberAsDefault,
            $data['payment_method'],
            $data['payment_account_id'] ?? null,
        );
        $request->session()->forget($this->selectionKey($token));

        $message = sprintf(
            'Bulk payment completed for %d parties with %d paid invoices. Total received: %s.',
            $result['count'],
            $result['invoice_count'],
            number_format($result['total'], 2),
        );

        if ($result['sync_failures'] > 0) {
            $message .= sprintf(' MikroTik sync failed for %d parties; database payments were saved.', $result['sync_failures']);
        }

        return redirect()->route('customers.index')->with('success', $message);
    }

    private function selectedCustomerIds(Request $request, string $token): array
    {
        abort_unless(preg_match('/^[a-f0-9]{40}$/', $token) === 1, 404);
        $ids = $request->session()->get($this->selectionKey($token));
        abort_unless(is_array($ids) && $ids !== [], 404);

        return array_values(array_unique(array_map('intval', $ids)));
    }

    private function selectionKey(string $token): string
    {
        return 'bulk_customer_payments.'.$token;
    }
}
