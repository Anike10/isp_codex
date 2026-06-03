<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type')->default('stock')->after('product_category_id')->index();
            $table->unsignedInteger('service_guarantee_days')->nullable()->after('warranty_days');
        });

        DB::table('products')
            ->where('track_inventory', false)
            ->update(['product_type' => 'service']);

        DB::table('products')
            ->where('track_inventory', true)
            ->where('track_serial_numbers', true)
            ->update(['product_type' => 'serial_stock']);

        Schema::table('product_serials', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('purchase_bill_item_id')->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->foreignId('invoice_item_id')->nullable()->after('invoice_id')->constrained('invoice_items')->nullOnDelete();
            $table->timestamp('sold_at')->nullable()->after('warranty_until');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('product_type')->nullable()->after('product_name');
            $table->unsignedInteger('warranty_days')->nullable()->after('serial_numbers');
            $table->unsignedInteger('service_guarantee_days')->nullable()->after('warranty_days');
            $table->date('service_guarantee_until')->nullable()->after('service_guarantee_days');
            $table->text('service_note')->nullable()->after('service_guarantee_until');
        });

        Schema::create('warranty_claims', function (Blueprint $table) {
            $table->id();
            $table->string('claim_no')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_item_id')->nullable()->constrained('invoice_items')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_serial_id')->nullable()->constrained()->nullOnDelete();
            $table->date('claim_date');
            $table->timestamp('received_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('warranty_status')->default('unknown')->index();
            $table->text('problem_description');
            $table->text('diagnosis_note')->nullable();
            $table->string('action_type')->default('repair')->index();
            $table->string('status')->default('pending')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('replacement_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('replacement_product_serial_id')->nullable()->constrained('product_serials')->nullOnDelete();
            $table->foreignId('service_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->decimal('service_charge', 12, 2)->default(0);
            $table->text('resolution_note')->nullable();
            $table->text('delivery_note')->nullable();
            $table->string('entry_by')->nullable();
            $table->string('entry_by_type')->nullable();
            $table->timestamps();
        });

        Schema::create('warranty_claim_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warranty_claim_id')->constrained()->cascadeOnDelete();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->text('note')->nullable();
            $table->string('entry_by')->nullable();
            $table->string('entry_by_type')->nullable();
            $table->timestamps();
        });

        $permissions = [
            'view_warranty_claims' => 'View warranty claims',
            'manage_warranty_claims' => 'Manage warranty claims',
            'close_warranty_claims' => 'Close warranty claims',
            'manage_service_products' => 'Manage service products',
        ];

        foreach ($permissions as $name => $label) {
            $permissionId = DB::table('permissions')->where('name', $name)->value('id');

            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $name,
                    'label' => $label,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $adminRoleId = DB::table('roles')->where('name', 'admin')->value('id');

            if ($adminRoleId && ! DB::table('permission_role')->where('role_id', $adminRoleId)->where('permission_id', $permissionId)->exists()) {
                DB::table('permission_role')->insert([
                    'role_id' => $adminRoleId,
                    'permission_id' => $permissionId,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warranty_claim_logs');
        Schema::dropIfExists('warranty_claims');

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['product_type', 'warranty_days', 'service_guarantee_days', 'service_guarantee_until', 'service_note']);
        });

        Schema::table('product_serials', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
            $table->dropConstrainedForeignId('invoice_id');
            $table->dropConstrainedForeignId('invoice_item_id');
            $table->dropColumn('sold_at');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['product_type', 'service_guarantee_days']);
        });
    }
};
