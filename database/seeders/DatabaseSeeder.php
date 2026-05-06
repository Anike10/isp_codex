<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\MikrotikRouter;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        $basic = InternetPackage::create([
            'name' => 'Home Basic',
            'speed' => '20 Mbps',
            'mikrotik_profile' => 'Home Basic',
            'monthly_price' => 800,
            'description' => 'Entry level home internet package.',
            'status' => 'active',
        ]);

        $premium = InternetPackage::create([
            'name' => 'Home Premium',
            'speed' => '50 Mbps',
            'mikrotik_profile' => 'Home Premium',
            'monthly_price' => 1500,
            'description' => 'Faster home and small office package.',
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'name' => 'Rahim Ahmed',
            'phone' => '01700000000',
            'email' => 'rahim@example.com',
            'connection_id' => 'KPS-1001',
            'mikrotik_username' => 'KPS-1001',
            'mikrotik_password' => '4321',
            'address' => 'Dhaka, Bangladesh',
            'status' => 'active',
        ]);

        Subscription::create([
            'customer_id' => $customer->id,
            'internet_package_id' => $premium->id,
            'start_date' => now()->startOfMonth(),
            'status' => 'active',
        ]);

        Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-'.now()->format('Ym').'-01001',
            'billing_month' => now()->format('Y-m'),
            'subtotal' => $premium->monthly_price,
            'discount' => 0,
            'total' => $premium->monthly_price,
            'paid_amount' => 0,
            'due_amount' => $premium->monthly_price,
            'status' => 'unpaid',
            'due_date' => now()->startOfMonth()->day(10),
        ]);

        SupportTicket::create([
            'customer_id' => $customer->id,
            'assigned_to' => $admin->id,
            'subject' => 'Slow internet speed',
            'description' => 'Customer reports unstable speed during evening hours.',
            'priority' => 'normal',
            'status' => 'open',
        ]);

        Product::create([
            'name' => 'Dual Band Router',
            'sku' => 'RTR-001',
            'category' => 'Router',
            'purchase_price' => 1800,
            'sale_price' => 2500,
            'stock_quantity' => 4,
            'low_stock_alert' => 5,
        ]);

        Product::create([
            'name' => 'CAT6 Cable',
            'sku' => 'CBL-001',
            'category' => 'Cable',
            'purchase_price' => 12,
            'sale_price' => 20,
            'stock_quantity' => 300,
            'low_stock_alert' => 50,
        ]);

        MikrotikRouter::create([
            'name' => 'Main MikroTik',
            'ip_address' => '192.168.6.1',
            'api_port' => 8728,
            'username' => 'admin',
            'password' => 'anikebd123',
            'status' => 'active',
            'notes' => 'Default router added from local setup.',
        ]);
    }
}
