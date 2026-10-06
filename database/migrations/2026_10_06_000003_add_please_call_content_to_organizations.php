<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('please_call_title')->nullable()->after('please_call_numbers');
            $table->text('please_call_message')->nullable()->after('please_call_title');
            $table->string('please_call_footer', 500)->nullable()->after('please_call_message');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn([
                'please_call_title',
                'please_call_message',
                'please_call_footer',
            ]);
        });
    }
};
