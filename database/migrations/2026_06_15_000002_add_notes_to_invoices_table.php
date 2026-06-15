<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->text('public_note')->nullable()->after('due_date');
            $table->boolean('show_public_note')->default(false)->after('public_note');
            $table->text('private_note')->nullable()->after('show_public_note');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['public_note', 'show_public_note', 'private_note']);
        });
    }
};
