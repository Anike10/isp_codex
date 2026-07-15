<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->boolean('default_without_signature')->default(false)->after('footer_note');
            $table->boolean('show_organization_selector')->default(true)->after('default_without_signature');
            $table->string('bank_name')->nullable()->after('show_organization_selector');
            $table->string('bank_account_name')->nullable()->after('bank_name');
            $table->string('bank_account_number', 100)->nullable()->after('bank_account_name');
            $table->string('bank_branch')->nullable()->after('bank_account_number');
            $table->string('bank_routing_number', 100)->nullable()->after('bank_branch');
            $table->boolean('show_bank_info_on_invoice')->default(false)->after('bank_routing_number');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['default_without_signature', 'show_organization_selector', 'bank_name', 'bank_account_name', 'bank_account_number', 'bank_branch', 'bank_routing_number', 'show_bank_info_on_invoice']);
        });
    }
};
