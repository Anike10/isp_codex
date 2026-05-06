<?php

namespace App\Http\Controllers;

use App\Models\BkashSmsPayment;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentAccount;
use App\Services\BillingService;
use App\Services\BkashSmsPaymentService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BkashSmsPaymentController extends Controller
{
    public function index(Request $request)
    {
        return view('bkash_sms_payments.index', [
            'smsPayments' => BkashSmsPayment::with(['customer', 'invoice', 'payment'])
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
        ]);

        $smsPayment = $smsPaymentService->handle($data['message'], $data['sender'] ?? 'bKash');

        return redirect()
            ->route('bkash-sms-payments.show', $smsPayment)
            ->with('success', 'bKash SMS entry saved with '.$smsPayment->status.' status.');
    }

    public function show(BkashSmsPayment $bkashSmsPayment)
    {
        $bkashSmsPayment->load(['customer', 'invoice', 'payment']);

        return view('bkash_sms_payments.show', [
            'bkashSmsPayment' => $bkashSmsPayment,
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'phone', 'connection_id', 'mikrotik_username']),
        ]);
    }

    public function approve(Request $request, BkashSmsPayment $bkashSmsPayment, BillingService $billingService, PaymentService $paymentService)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
        ]);

        if (! in_array($bkashSmsPayment->status, ['pending', 'failed'], true)) {
            return back()->withErrors(['sms' => 'Only pending or failed SMS entries can be approved manually.']);
        }

        if (! $bkashSmsPayment->amount || ! $bkashSmsPayment->trx_id) {
            return back()->withErrors(['sms' => 'This SMS does not have a valid amount or TrxID.']);
        }

        $customer = Customer::findOrFail($data['customer_id']);
        $billingService->generateCurrentServiceBillForCustomer($customer);

        $invoice = Invoice::where('customer_id', $customer->id)
            ->where('due_amount', '>', 0)
            ->orderBy('due_date')
            ->orderBy('id')
            ->first();

        if (! $invoice) {
            $customer->increment('account_balance', $bkashSmsPayment->amount);
            $bkashSmsPayment->update([
                'status' => 'balance',
                'customer_id' => $customer->id,
                'message' => 'Manually approved. No due invoice found. Amount added to customer balance.',
            ]);

            return redirect()->route('bkash-sms-payments.show', $bkashSmsPayment)->with('success', 'SMS approved and amount added to customer balance.');
        }

        $paymentAccount = $bkashSmsPayment->customer_number
            ? PaymentAccount::firstOrCreate(
                [
                    'payment_method' => 'bkash',
                    'account_number' => $bkashSmsPayment->customer_number,
                ],
                [
                    'account_name' => 'bKash Sender '.$bkashSmsPayment->customer_number,
                    'opening_balance' => 0,
                    'status' => 'active',
                ]
            )
            : null;

        $payment = $paymentService->recordPayment($invoice, [
            'amount' => $bkashSmsPayment->amount,
            'payment_method' => 'bkash',
            'payment_account_id' => $paymentAccount?->id,
            'payment_date' => $bkashSmsPayment->payment_date?->toDateString() ?? now()->toDateString(),
            'note' => 'Manual approve bKash SMS TrxID: '.$bkashSmsPayment->trx_id,
        ]);

        $bkashSmsPayment->update([
            'status' => 'processed',
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
            'message' => 'Manually approved and payment recorded.',
        ]);

        return redirect()->route('bkash-sms-payments.show', $bkashSmsPayment)->with('success', 'SMS approved and payment recorded.');
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
        ]);

        $smsPayment = $smsPaymentService->handle($data['message'] ?? $data['sms'], $data['sender'] ?? null);

        return response()->json([
            'id' => $smsPayment->id,
            'status' => $smsPayment->status,
            'trx_id' => $smsPayment->trx_id,
            'reference' => $smsPayment->reference,
            'amount' => $smsPayment->amount,
            'message' => $smsPayment->message,
        ]);
    }
}
