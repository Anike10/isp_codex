<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_accounts', function (Blueprint $table): void {
            $table->foreignId('owner_user_id')
                ->nullable()
                ->after('entry_by')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Adopt the account's creator as its owner where entry_by holds a real
        // user id. Accounts left unowned are super-admin only until assigned.
        $userIds = DB::table('users')->pluck('id')->map(fn ($id) => (int) $id)->all();

        DB::table('payment_accounts')
            ->whereNull('owner_user_id')
            ->orderBy('id')
            ->get(['id', 'entry_by'])
            ->each(function (object $account) use ($userIds): void {
                if (is_numeric($account->entry_by) && in_array((int) $account->entry_by, $userIds, true)) {
                    DB::table('payment_accounts')
                        ->where('id', $account->id)
                        ->update(['owner_user_id' => (int) $account->entry_by]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('payment_accounts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('owner_user_id');
        });
    }
};
