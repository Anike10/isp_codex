<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('designation')->nullable();
            $table->string('phone')->nullable();
            $table->date('join_date')->nullable();
            $table->decimal('current_salary', 12, 2)->default(0);
            $table->date('salary_effective_from')->nullable();
            $table->unsignedTinyInteger('yearly_bonus_count')->default(0);
            $table->decimal('bonus_percent', 5, 2)->default(0);
            $table->string('status')->default('active');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_salary_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('old_salary', 12, 2)->default(0);
            $table->decimal('new_salary', 12, 2);
            $table->date('effective_from');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'effective_from']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->after('category')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('employee_id');
        });

        Schema::dropIfExists('employee_salary_revisions');
        Schema::dropIfExists('employees');
    }
};
