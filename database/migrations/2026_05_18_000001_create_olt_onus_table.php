<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('olt_onus', function (Blueprint $table) {
            $table->id();
            $table->string('entry_by')->nullable()->index();
            $table->string('olt_name')->nullable()->index();
            $table->unsignedTinyInteger('pon_port');
            $table->unsignedSmallInteger('onu_id');
            $table->string('mac_address', 32)->nullable()->index();
            $table->string('onu_type')->nullable();
            $table->string('name')->nullable()->index();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('parent_splitter')->nullable();
            $table->json('port_vlans')->nullable();
            $table->decimal('rx_power_dbm', 6, 2)->nullable()->index();
            $table->string('power_note')->nullable();
            $table->text('raw_bind_config')->nullable();
            $table->text('raw_interface_config')->nullable();
            $table->timestamp('last_backup_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['pon_port', 'onu_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('olt_onus');
    }
};
