<?php

namespace App\Http\Controllers;

use App\Models\BkashSmsPayment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\AdvanceRenewalService;
use App\Services\BillingService;
use App\Services\BkashSmsPaymentService;
use App\Services\BkashSmsRetentionService;
use App\Services\PaymentService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BkashSmsPaymentController extends Controller
{
    public function index(Request $request, BkashSmsRetentionService $retention, WhatsAppService $whatsapp)
    {
        return view('bkash_sms_payments.index', [
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'connection_id', 'mikrotik_username']),
            'retentionDays' => $retention->retentionDays(),
            'junkAutoDelete' => $retention->junkAutoDelete(),
            'whatsappEnabled' => $whatsapp->isEnabled(),
            'whatsappConfigured' => $whatsapp->isConfigured(),
            'whatsappStatuses' => $whatsapp->notifyStatuses(),
            'failedCount' => BkashSmsPayment::where('status', 'failed')->count(),
            'junkFailedCount' => BkashSmsPayment::where('status', 'failed')->whereNull('trx_id')->whereNull('amount')->count(),
            'smsPayments' => BkashSmsPayment::with(['customer', 'invoice', 'payment'])
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = trim((string) $request->query('search'));
                    $query->where(function ($query) use ($search) {
                        $query->where('trx_id', 'like', "%{$search}%")
                            ->orWhere('ledger_trx_id', 'like', "%{$search}%")
                            ->orWhere('reference', 'like', "%{$search}%")
                            ->orWhere('customer_number', 'like', "%{$search}%")
                            ->orWhere('message', 'like', "%{$search}%")
                            ->orWhere('raw_sms', 'like', "%{$search}%")
                            ->orWhereHas('customer', fn ($query) => $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%")
                                ->orWhere('connection_id', 'like', "%{$search}%"))
                            ->orWhereHas('invoice', fn ($query) => $query->where('invoice_no', 'like', "%{$search}%"));
                    });
                })
                ->when($request->filled('status'), function ($query) use ($request) {
                    $status = $request->query('status');

                    match ($status) {
                        'auto' => $query->where('status', 'processed')->whereNull('paid_by_name'),
                        'manual' => $query->where('status', 'processed')->whereNotNull('paid_by_name'),
                        default => $query->where('status', $status),
                    };
                })
                ->when($request->filled('from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('from')))
                ->when($request->filled('to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('to')))
                ->when($request->filled('min_amount'), fn ($query) => $query->where('amount', '>=', (float) $request->query('min_amount')))
                ->when($request->filled('max_amount'), fn ($query) => $query->where('amount', '<=', (float) $request->query('max_amount')))
                ->latest()
                ->paginate($this->perPage($request))
                ->appends($request->query()),
        ]);
    }

    public function create()
    {
        return view('bkash_sms_payments.create');
    }

    public function manualStore(Request $request, BkashSmsPaymentService $smsPaymentService)
    {
        $data = $request->validate([
            'message' => ['required', 'string'],
            'sender' => ['nullable', 'string', 'max:100'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device' => ['nullable', 'string', 'max:255'],
            'phone_name' => ['nullable', 'string', 'max:255'],
            'sender_device' => ['nullable', 'string', 'max:255'],
            'senderDevice' => ['nullable', 'string', 'max:255'],
            'deviceName' => ['nullable', 'string', 'max:255'],
        ]);

        $deviceName = $this->deviceNameFrom($data);
        $smsPayment = $smsPaymentService->handle($data['message'], $data['sender'] ?? 'bKash', $deviceName);

        return redirect()
            ->route('bkash-sms-payments.show', $smsPayment)
            ->with('success', 'bKash SMS entry saved with '.$smsPayment->status.' status.');
    }

    public function show(BkashSmsPayment $bkashSmsPayment, BkashSmsPaymentService $smsPaymentService)
    {
        $bkashSmsPayment->load(['customer', 'invoice', 'payment']);

        $match = $smsPaymentService->identifyCustomer(
            $bkashSmsPayment->reference,
            $bkashSmsPayment->customer_number,
        );

        $manualCandidates = collect();
        if (! empty($match['candidate_customer_ids'])) {
            $manualCandidates = Customer::query()
                ->whereIn('id', $match['candidate_customer_ids'])
                ->orderBy('name')
                ->get(['id', 'name', 'phone', 'connection_id', 'mikrotik_username', 'is_customer', 'is_vendor']);
        }

        return view('bkash_sms_payments.show', [
            'bkashSmsPayment' => $bkashSmsPayment,
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'phone', 'connection_id', 'mikrotik_username', 'is_customer', 'is_vendor']),
            'manualCandidates' => $manualCandidates,
            'matchMessageHint' => $match['message'],
        ]);
    }

    public function approve(Request $request, BkashSmsPayment $bkashSmsPayment, BillingService $billingService, PaymentService $paymentService, AdvanceRenewalService $advanceRenewalService, BkashSmsPaymentService $smsPaymentService)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
        ]);

        $deviceName = $this->deviceNameFrom($request->all());
        $adminName = $request->user()?->name;
        $redirect = fn () => $request->input('redirect_to') === 'index'
            ? redirect()->route('bkash-sms-payments.index')
            : redirect()->route('bkash-sms-payments.show', $bkashSmsPayment);

        $prepared = DB::transaction(function () use ($bkashSmsPayment, $smsPaymentService, $data, $deviceName, $adminName) {
            $smsPayment = BkashSmsPayment::whereKey($bkashSmsPayment->id)->lockForUpdate()->firstOrFail();

            if (! in_array($smsPayment->status, ['pending', 'failed'], true)) {
                return ['error' => 'Only pending or failed SMS entries can be approved manually.'];
            }

            if (! $smsPayment->amount || ! $smsPayment->trx_id) {
                return ['error' => 'This SMS does not have a valid amount or TrxID.'];
            }

            $paymentAccount = $smsPaymentService->resolveSmsDeviceAccount($smsPayment->raw_sms, $smsPayment->sms_sender, $deviceName);
            $entryBy = $paymentAccount?->account_name ?: $smsPayment->sms_sender;

            $smsPayment->forceFill([
                'status' => 'processing',
                'entry_by' => $smsPayment->entry_by ?: $entryBy,
                'paid_by_name' => $adminName ?: $smsPayment->paid_by_name,
                'message' => 'Manual approval is processing.',
            ])->save();

            return [
                'sms_payment_id' => $smsPayment->id,
                'customer_id' => $data['customer_id'],
                'payment_account_id' => $paymentAccount?->id,
                'entry_by' => $entryBy,
            ];
        });

        if (isset($prepared['error'])) {
            return back()->withErrors(['sms' => $prepared['error']]);
        }

        $smsPayment = BkashSmsPayment::findOrFail($prepared['sms_payment_id']);
        $customer = Customer::findOrFail($prepared['customer_id']);

        try {
            $billingService->generateCurrentServiceBillForCustomer($customer);

            $invoice = Invoice::where('customer_id', $customer->id)
                ->where('due_amount', '>', 0)
                ->orderBy('due_date')
                ->orderBy('id')
                ->first();

            if (! $invoice) {
                $paymentDate = $smsPayment->payment_date?->toDateString() ?? now()->toDateString();
                $paymentService->addAdvanceCredit($customer, [
                    'amount' => $smsPayment->amount,
                    'payment_method' => 'bkash',
                    'payment_account_id' => $prepared['payment_account_id'],
                    'payment_date' => $paymentDate,
                    'reference' => $smsPayment->trx_id,
                    'entry_by' => $prepared['entry_by'],
                    'note' => 'Manual approve bKash SMS advance TrxID: '.$smsPayment->trx_id,
                ]);

                $renewedMonths = $advanceRenewalService->renew(
                    $customer,
                    $paymentDate,
                    24,
                    'Approved bKash renewal from advance balance. TrxID: '.$smsPayment->trx_id,
                );

                if ($renewedMonths > 0) {
                    $renewalInvoice = Invoice::query()
                        ->where('customer_id', $customer->id)
                        ->where('invoice_type', 'service')
                        ->where('due_amount', '<=', 0)
                        ->latest('id')
                        ->first();

                    $smsPayment->update([
                        'status' => 'processed',
                        'customer_id' => $customer->id,
                        'invoice_id' => $renewalInvoice?->id,
                        'message' => "Manually approved and {$renewedMonths} package month(s) renewed automatically from advance balance.",
                    ]);

                    return $redirect()
                        ->with('success', "SMS approved and {$renewedMonths} package month(s) renewed.");
                }

                $smsPayment->update([
                    'status' => 'balance',
                    'customer_id' => $customer->id,
                    'message' => 'Manually approved. No due invoice found. Amount added to party balance ledger.',
                ]);

                return $redirect()->with('success', 'SMS approved and amount added to party balance.');
            }

            $payment = $paymentService->recordPayment($invoice, [
                'amount' => $smsPayment->amount,
                'payment_method' => 'bkash',
                'payment_account_id' => $prepared['payment_account_id'],
                'payment_date' => $smsPayment->payment_date?->toDateString() ?? now()->toDateString(),
                'reference' => $smsPayment->trx_id,
                'entry_by' => $prepared['entry_by'],
                'note' => 'Manual approve bKash SMS TrxID: '.$smsPayment->trx_id,
            ]);

            $smsPayment->update([
                'status' => 'processed',
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'message' => 'Manually approved and payment recorded.',
            ]);
        } catch (Throwable $exception) {
            $smsPayment->update([
                'status' => 'failed',
                'message' => 'Manual approval failed: '.$exception->getMessage(),
            ]);

            return back()->withErrors(['sms' => 'Manual approval failed: '.$exception->getMessage()]);
        }

        return $redirect()->with('success', 'SMS approved and payment recorded.');
    }

    public function maintenance(Request $request, BkashSmsRetentionService $retention)
    {
        $data = $request->validate([
            'action' => ['required', 'in:save,prune_old,delete_failed,prune_junk'],
            'retention_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
            'autodelete_junk' => ['nullable', 'boolean'],
        ]);

        // Every submit carries the current settings; persist them first.
        $retention->setRetentionDays((int) ($data['retention_days'] ?? 0));
        $retention->setJunkAutoDelete((bool) ($data['autodelete_junk'] ?? false));

        $message = match ($data['action']) {
            'prune_old' => 'Deleted '.$retention->pruneOldRows().' SMS row(s) older than the retention window.',
            'delete_failed' => 'Deleted '.$retention->deleteFailedRows().' failed SMS row(s).',
            'prune_junk' => 'Deleted '.$retention->pruneJunkFailedRows().' non-payment (junk) failed SMS row(s).',
            default => 'Cleanup settings saved.',
        };

        return redirect()->route('bkash-sms-payments.index')->with('success', $message);
    }

    public function whatsappSettings(Request $request, WhatsAppService $whatsapp)
    {
        $data = $request->validate([
            'action' => ['required', 'in:save,test'],
            'enabled' => ['nullable', 'boolean'],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['in:processed,balance'],
            'test_number' => ['nullable', 'string', 'max:20'],
        ]);

        $whatsapp->setEnabled((bool) ($data['enabled'] ?? false));
        $whatsapp->setNotifyStatuses($data['statuses'] ?? []);

        if ($data['action'] === 'test') {
            $result = $whatsapp->sendTest((string) ($data['test_number'] ?? ''));

            return redirect()->route('bkash-sms-payments.index')
                ->with($result['ok'] ? 'success' : 'error', $result['message']);
        }

        return redirect()->route('bkash-sms-payments.index')->with('success', 'WhatsApp reply settings saved.');
    }

    public function whatsappResend(BkashSmsPayment $bkashSmsPayment, WhatsAppService $whatsapp)
    {
        if (! $whatsapp->isConfigured()) {
            return back()->with('error', 'WhatsApp Cloud API credentials are not set in the environment.');
        }

        if (! in_array($bkashSmsPayment->status, BkashSmsPayment::NOTIFIABLE_STATUSES, true)) {
            return back()->with('error', 'Only processed or balance rows can send a WhatsApp confirmation.');
        }

        $bkashSmsPayment->forceFill(['whatsapp_status' => null, 'whatsapp_error' => null])->save();
        $whatsapp->sendPaymentConfirmation($bkashSmsPayment->fresh('customer'));

        $sent = $bkashSmsPayment->fresh()->whatsapp_status === 'sent';

        return back()->with($sent ? 'success' : 'error', $sent
            ? 'WhatsApp confirmation sent.'
            : 'WhatsApp send failed: '.($bkashSmsPayment->fresh()->whatsapp_error ?: 'unknown error'));
    }

    public function store(Request $request, BkashSmsPaymentService $smsPaymentService)
    {
        Log::info('bKash SMS webhook received', [
            'ip' => $request->ip(),
            'content_type' => $request->header('content-type'),
            'headers' => [
                'x_sms_token' => $request->header('X-SMS-Token') ? 'present' : 'missing',
                'authorization' => $request->bearerToken() ? 'present' : 'missing',
            ],
            'payload' => $request->all(),
            'raw' => $request->getContent(),
        ]);

        $expectedToken = config('services.bkash_sms.token');
        $providedToken = $request->bearerToken() ?: $request->header('X-SMS-Token') ?: $request->input('token');

        if ($expectedToken && ! hash_equals($expectedToken, (string) $providedToken)) {
            return response()->json(['message' => 'Invalid SMS webhook token.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->validate([
            'message' => ['required_without:sms', 'string'],
            'sms' => ['required_without:message', 'string'],
            'sender' => ['nullable', 'string', 'max:100'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device' => ['nullable', 'string', 'max:255'],
            'phone_name' => ['nullable', 'string', 'max:255'],
            'sender_device' => ['nullable', 'string', 'max:255'],
            'senderDevice' => ['nullable', 'string', 'max:255'],
            'deviceName' => ['nullable', 'string', 'max:255'],
        ]);

        $deviceName = $this->deviceNameFrom($data);
        $smsPayment = $smsPaymentService->handle($data['message'] ?? $data['sms'], $data['sender'] ?? null, $deviceName);

        return response()->json([
            'id' => $smsPayment->id,
            'status' => $smsPayment->status,
            'trx_id' => $smsPayment->trx_id,
            'reference' => $smsPayment->reference,
            'amount' => $smsPayment->amount,
            'message' => $smsPayment->message,
        ]);
    }

    private function deviceNameFrom(array $data): ?string
    {
        foreach (['device_name', 'sender_device', 'senderDevice', 'deviceName', 'device', 'phone_name'] as $key) {
            $value = trim((string) ($data[$key] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }
}
