<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bkash_sms_payments', function (Blueprint $table): void {
            // null = never attempted; otherwise sent / failed / skipped
            $table->string('whatsapp_status')->nullable()->after('message');
            $table->string('whatsapp_to')->nullable()->after('whatsapp_status');
            $table->string('whatsapp_message_id')->nullable()->after('whatsapp_to');
            $table->string('whatsapp_error', 500)->nullable()->after('whatsapp_message_id');
            $table->timestamp('whatsapp_sent_at')->nullable()->after('whatsapp_error');
        });
    }

    public function down(): void
    {
        Schema::table('bkash_sms_payments', function (Blueprint $table): void {
            $table->dropColumn([
                'whatsapp_status',
                'whatsapp_to',
                'whatsapp_message_id',
                'whatsapp_error',
                'whatsapp_sent_at',
            ]);
        });
    }
};
