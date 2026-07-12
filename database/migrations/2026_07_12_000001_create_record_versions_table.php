<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('record_versions', function (Blueprint $table): void {
            $table->id();
            $table->string('versionable_type')->index();
            $table->unsignedBigInteger('versionable_id')->index();
            $table->string('table_name')->index();
            $table->string('action')->default('updated')->index();
            $table->string('edited_by')->nullable()->index();
            $table->string('edited_by_type')->nullable()->index();
            $table->string('edited_by_name')->nullable();
            $table->json('old_values');
            $table->json('new_values')->nullable();
            $table->json('changed_fields')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['versionable_type', 'versionable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('record_versions');
    }
};
