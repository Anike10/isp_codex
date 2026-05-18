<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olt_devices', function (Blueprint $table) {
            $table->id();
            $table->string('entry_by')->nullable()->index();
            $table->string('name');
            $table->string('host');
            $table->unsignedSmallInteger('port')->default(22);
            $table->string('username');
            $table->text('password');
            $table->text('enable_password')->nullable();
            $table->string('onu_status_command')->default('show onu-info all');
            $table->string('onu_power_command')->default('show optical-info');
            $table->string('status')->default('unknown')->index();
            $table->timestamp('last_polled_at')->nullable();
            $table->text('last_error')->nullable();
            $table->longText('last_raw_output')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('olt_onus', function (Blueprint $table) {
            $table->foreignId('olt_device_id')->nullable()->after('entry_by')->constrained('olt_devices')->nullOnDelete();
            $table->string('status')->nullable()->after('onu_type')->index();
            $table->unsignedInteger('distance_m')->nullable()->after('power_note');
            $table->longText('raw_live_output')->nullable()->after('raw_interface_config');
            $table->timestamp('last_live_polled_at')->nullable()->after('last_backup_at')->index();
            $table->dropUnique(['pon_port', 'onu_id']);
            $table->unique(['olt_device_id', 'pon_port', 'onu_id']);
        });
    }

    public function down(): void
    {
        Schema::table('olt_onus', function (Blueprint $table) {
            $table->dropUnique(['olt_device_id', 'pon_port', 'onu_id']);
            $table->unique(['pon_port', 'onu_id']);
            $table->dropConstrainedForeignId('olt_device_id');
            $table->dropColumn(['status', 'distance_m', 'raw_live_output', 'last_live_polled_at']);
        });

        Schema::dropIfExists('olt_devices');
    }
};
