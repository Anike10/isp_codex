<?php

namespace App\Jobs;

use App\Models\BkashSmsPayment;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendBkashWhatsAppReply implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var int[] */
    public array $backoff = [60, 300];

    public function __construct(public int $smsPaymentId) {}

    public function handle(WhatsAppService $whatsapp): void
    {
        $sms = BkashSmsPayment::with('customer')->find($this->smsPaymentId);

        if (! $sms || ! $whatsapp->shouldNotify($sms)) {
            return;
        }

        $whatsapp->sendPaymentConfirmation($sms);
    }
}
