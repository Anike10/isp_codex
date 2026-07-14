<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_asset_assignments', function (Blueprint $table): void {
            $table->string('approval_document_path')->nullable()->after('note');
            $table->string('approval_document_name')->nullable()->after('approval_document_path');
            $table->string('approval_document_mime', 100)->nullable()->after('approval_document_name');
        });

        Schema::table('purchase_bills', function (Blueprint $table): void {
            $table->string('document_path')->nullable()->after('note');
            $table->string('document_name')->nullable()->after('document_path');
            $table->string('document_mime', 100)->nullable()->after('document_name');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_bills', function (Blueprint $table): void {
            $table->dropColumn(['document_path', 'document_name', 'document_mime']);
        });

        Schema::table('employee_asset_assignments', function (Blueprint $table): void {
            $table->dropColumn(['approval_document_path', 'approval_document_name', 'approval_document_mime']);
        });
    }
};
