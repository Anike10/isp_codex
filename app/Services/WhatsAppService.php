<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\BkashSmsPayment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppService
{
    /** Master on/off for the bKash payment-received WhatsApp reply. */
    public const ENABLED_KEY = 'whatsapp_reply_enabled';

    /** CSV of bKash SMS statuses that should trigger a reply. */
    public const STATUSES_KEY = 'whatsapp_reply_statuses';

    public function isEnabled(): bool
    {
        return AppSetting::value(self::ENABLED_KEY, '0') === '1';
    }

    public function setEnabled(bool $on): void
    {
        AppSetting::setValue(self::ENABLED_KEY, $on ? '1' : '0');
    }

    /** @return string[] */
    public function notifyStatuses(): array
    {
        $raw = AppSetting::value(self::STATUSES_KEY, implode(',', BkashSmsPayment::NOTIFIABLE_STATUSES));
        $statuses = array_values(array_intersect(
            array_filter(array_map('trim', explode(',', (string) $raw))),
            BkashSmsPayment::NOTIFIABLE_STATUSES,
        ));

        return $statuses ?: BkashSmsPayment::NOTIFIABLE_STATUSES;
    }

    /** @param string[] $statuses */
    public function setNotifyStatuses(array $statuses): void
    {
        $clean = array_values(array_intersect($statuses, BkashSmsPayment::NOTIFIABLE_STATUSES));
        AppSetting::setValue(self::STATUSES_KEY, implode(',', $clean ?: BkashSmsPayment::NOTIFIABLE_STATUSES));
    }

    /** All three Cloud API credentials are present. */
    public function isConfigured(): bool
    {
        return filled(config('services.whatsapp.token'))
            && filled(config('services.whatsapp.phone_number_id'))
            && filled(config('services.whatsapp.payment_template'));
    }

    /**
     * Whether this row should get a WhatsApp confirmation right now:
     * feature on, credentials present, status is in the notify list,
     * a recipient number can be resolved, and it was not sent already.
     */
    public function shouldNotify(BkashSmsPayment $sms): bool
    {
        return $this->isEnabled()
            && $this->isConfigured()
            && $sms->whatsapp_status !== 'sent'
            && in_array($sms->status, $this->notifyStatuses(), true)
            && $this->recipientFor($sms) !== null;
    }

    /**
     * Resolve the number to message: the matched party's first valid
     * mobile, else the number parsed from the SMS. Returned in the
     * Cloud API's wa_id form (digits only, 880XXXXXXXXXX).
     */
    public function recipientFor(BkashSmsPayment $sms): ?string
    {
        $candidates = [];

        if ($sms->customer && $sms->customer->phone) {
            $candidates = preg_split('/[\s,;|\/]+/u', trim($sms->customer->phone)) ?: [];
        }

        $candidates[] = $sms->customer_number;

        foreach ($candidates as $candidate) {
            $wid = $this->toWaId($candidate);

            if ($wid !== null) {
                return $wid;
            }
        }

        return null;
    }

    /** Normalise a Bangladeshi mobile to 8801XXXXXXXXX, or null if not one. */
    public function toWaId(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '8801') && strlen($digits) === 13) {
            return $digits;
        }

        if (str_starts_with($digits, '01') && strlen($digits) === 11) {
            return '88'.$digits;
        }

        if (str_starts_with($digits, '1') && strlen($digits) === 10) {
            return '880'.$digits;
        }

        return null;
    }

    /**
     * Send the payment-received template for this row and record the
     * outcome on it. Never throws — failures are stored on the row.
     */
    public function sendPaymentConfirmation(BkashSmsPayment $sms): void
    {
        $to = $this->recipientFor($sms);

        if ($to === null) {
            $sms->forceFill([
                'whatsapp_status' => 'skipped',
                'whatsapp_error' => 'No Bangladeshi mobile number to message.',
                'whatsapp_sent_at' => null,
            ])->save();

            return;
        }

        $partyName = $sms->customer?->name ?: 'Customer';
        $amount = $sms->amount !== null ? number_format((float) $sms->amount, 2) : '';
        $date = optional($sms->payment_date)->format('d/m/Y') ?: now()->format('d/m/Y');

        try {
            $response = Http::withToken(config('services.whatsapp.token'))
                ->acceptJson()
                ->post($this->endpoint(), [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'template',
                    'template' => [
                        'name' => config('services.whatsapp.payment_template'),
                        'language' => ['code' => config('services.whatsapp.payment_template_language', 'en')],
                        'components' => [[
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => $partyName],
                                ['type' => 'text', 'text' => $amount],
                                ['type' => 'text', 'text' => (string) ($sms->trx_id ?? '')],
                                ['type' => 'text', 'text' => $date],
                            ],
                        ]],
                    ],
                ]);
        } catch (Throwable $exception) {
            Log::warning('WhatsApp payment reply failed', ['sms_id' => $sms->id, 'error' => $exception->getMessage()]);

            $sms->forceFill([
                'whatsapp_status' => 'failed',
                'whatsapp_to' => $to,
                'whatsapp_error' => mb_substr($exception->getMessage(), 0, 480),
                'whatsapp_sent_at' => null,
            ])->save();

            return;
        }

        if ($response->successful()) {
            $sms->forceFill([
                'whatsapp_status' => 'sent',
                'whatsapp_to' => $to,
                'whatsapp_message_id' => data_get($response->json(), 'messages.0.id'),
                'whatsapp_error' => null,
                'whatsapp_sent_at' => now(),
            ])->save();

            return;
        }

        $sms->forceFill([
            'whatsapp_status' => 'failed',
            'whatsapp_to' => $to,
            'whatsapp_error' => mb_substr((string) (data_get($response->json(), 'error.message') ?: $response->body()), 0, 480),
            'whatsapp_sent_at' => null,
        ])->save();
    }

    /** Fire the template at an arbitrary number for a settings-page test. */
    public function sendTest(string $phone): array
    {
        $to = $this->toWaId($phone);

        if ($to === null) {
            return ['ok' => false, 'message' => 'Enter a valid Bangladeshi mobile number (01XXXXXXXXX).'];
        }

        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'WhatsApp Cloud API credentials are not set in the environment.'];
        }

        try {
            $response = Http::withToken(config('services.whatsapp.token'))
                ->acceptJson()
                ->post($this->endpoint(), [
                    'messaging_product' => 'whatsapp',
                    'to' => $to,
                    'type' => 'template',
                    'template' => [
                        'name' => config('services.whatsapp.payment_template'),
                        'language' => ['code' => config('services.whatsapp.payment_template_language', 'en')],
                        'components' => [[
                            'type' => 'body',
                            'parameters' => [
                                ['type' => 'text', 'text' => 'Test Customer'],
                                ['type' => 'text', 'text' => '100.00'],
                                ['type' => 'text', 'text' => 'TESTTRX123'],
                                ['type' => 'text', 'text' => now()->format('d/m/Y')],
                            ],
                        ]],
                    ],
                ]);
        } catch (Throwable $exception) {
            return ['ok' => false, 'message' => $exception->getMessage()];
        }

        return $response->successful()
            ? ['ok' => true, 'message' => 'Test message sent to '.$to.'.']
            : ['ok' => false, 'message' => (string) (data_get($response->json(), 'error.message') ?: $response->body())];
    }

    private function endpoint(): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            config('services.whatsapp.api_version', 'v21.0'),
            config('services.whatsapp.phone_number_id'),
        );
    }
}
