<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table): void {
            // How the app talks to this router:
            //  - "api"  : the binary RouterOS API on api_port (default 8728)
            //  - "rest" : the RouterOS v7 REST API served by the "www" service
            //             on api_port (import only; the app never pushes over it)
            $table->string('transport', 16)->default('api')->after('api_port');
            // REST over https (www-ssl / self-signed) instead of plain http.
            $table->boolean('rest_secure')->default(false)->after('transport');
        });
    }

    public function down(): void
    {
        Schema::table('mikrotik_routers', function (Blueprint $table): void {
            $table->dropColumn(['transport', 'rest_secure']);
        });
    }
};
