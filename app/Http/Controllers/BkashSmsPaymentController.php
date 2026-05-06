<?php

namespace App\Http\Controllers;

use App\Models\BkashSmsPayment;
use App\Services\BkashSmsPaymentService;
use Illuminate\Http\Request;
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

        return view('bkash_sms_payments.show', compact('bkashSmsPayment'));
    }

    public function store(Request $request, BkashSmsPaymentService $smsPaymentService)
    {
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
