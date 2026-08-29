<?php

namespace App\Models;

use App\Jobs\SendBkashWhatsAppReply;
use App\Services\WhatsAppService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BkashSmsPayment extends Model
{
    use HasFactory;

    /** Statuses that make a payment "confirmed" enough to notify the payer. */
    public const NOTIFIABLE_STATUSES = ['processed', 'balance'];

    protected $fillable = [
        'entry_by',
        'paid_by_name',
        'sms_sender',
        'raw_sms',
        'customer_number',
        'trx_id',
        'ledger_trx_id',
        'reference',
        'amount',
        'payment_date',
        'status',
        'customer_id',
        'invoice_id',
        'payment_id',
        'message',
        'whatsapp_status',
        'whatsapp_to',
        'whatsapp_message_id',
        'whatsapp_error',
        'whatsapp_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'whatsapp_sent_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Fire the payer's WhatsApp confirmation the moment a row lands on
        // (or moves into) a confirmed status and has not been notified yet.
        // Runs for every intake path — webhook, manual entry, manual approve.
        static::saved(function (BkashSmsPayment $sms): void {
            if ($sms->whatsapp_status !== null) {
                return;
            }

            if (! $sms->wasRecentlyCreated && ! $sms->wasChanged('status')) {
                return;
            }

            if (! in_array($sms->status, self::NOTIFIABLE_STATUSES, true)) {
                return;
            }

            if (app(WhatsAppService::class)->isEnabled()) {
                SendBkashWhatsAppReply::dispatch($sms->id)->afterCommit();
            }
        });
    }

    /**
     * How the row reads in the Status column. A processed row is split
     * into "auto" (matched and posted by the SMS webhook) vs "manual"
     * (an admin approved it — paid_by_name is stamped). Every other
     * status shows as-is (pending, failed, balance, duplicate).
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->status === 'processed') {
            return $this->paid_by_name ? 'manual' : 'auto';
        }

        return (string) $this->status;
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
