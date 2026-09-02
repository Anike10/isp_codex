<?php

namespace Tests\Feature;

use App\Models\AppIpPool;
use App\Models\Customer;
use App\Models\InternetPackage;
use App\Models\Invoice;
use App\Models\MikrotikImportedSecret;
use App\Models\MikrotikRouter;
use App\Models\OltOnu;
use App\Models\Permission;
use App\Models\Subscription;
use App\Models\User;
use App\Services\MikrotikCustomerSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_update_does_not_decrypt_an_unchanged_mikrotik_password(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $package = InternetPackage::create([
            'name' => 'Imported Key Package',
            'speed' => '30 Mbps',
            'monthly_price' => 1000,
            'status' => 'active',
        ]);
        $router = MikrotikRouter::create([
            'name' => 'Imported Router',
            'ip_address' => '10.10.10.1',
            'api_port' => 8728,
            'username' => 'admin',
            'password' => 'router-password',
            'status' => 'active',
        ]);
        $customer = Customer::create([
            'name' => 'Imported Customer',
            'phone' => '01700000347',
            'connection_id' => 'IMPORTED-347',
            'mikrotik_username' => 'IMPORTED-347',
            'mikrotik_password' => 'original-password',
            'mikrotik_router_id' => $router->id,
            'address' => 'Imported address',
            'status' => 'active',
            'is_customer' => true,
        ]);
        $customer->mikrotikRouters()->attach($router);
        Subscription::create([
            'customer_id' => $customer->id,
            'internet_package_id' => $package->id,
            'start_date' => '2026-08-10',
            'status' => 'active',
        ]);

        $foreignCiphertext = 'ciphertext-created-with-another-app-key';
        DB::table('customers')->where('id', $customer->id)->update([
            'mikrotik_password' => $foreignCiphertext,
        ]);

        $syncService = \Mockery::mock(MikrotikCustomerSyncService::class);
        $syncService->shouldReceive('sync')->once()->andReturn('updated');
        $this->app->instance(MikrotikCustomerSyncService::class, $syncService);

        $this->actingAs($user)->put(route('customers.update', $customer), [
            'name' => 'Imported Customer Updated',
            'phone' => '01700000347',
            'connection_id' => 'IMPORTED-347',
            'mikrotik_router_ids' => [$router->id],
            'address' => 'Imported address updated',
            'status' => 'active',
            'is_customer' => '1',
            'internet_package_id' => $package->id,
            'start_date' => '2026-08-10',
        ])->assertRedirect(route('customers.show', $customer))
            ->assertSessionHasNoErrors();

        $updatedCustomer = Customer::query()->findOrFail($customer->id);
        $this->assertSame('Imported Customer Updated', $updatedCustomer->name);
        $this->assertSame($foreignCiphertext, $updatedCustomer->getRawOriginal('mikrotik_password'));
    }

    public function test_product_only_customer_can_be_created_without_connection_id(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'Walk In Buyer',
            'phone' => '01711111111',
            'email' => '',
            'connection_id' => '',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => '1',
        ])->assertRedirect(route('customers.index'));

        $customer = Customer::where('phone', '01711111111')->firstOrFail();

        $this->assertNull($customer->connection_id);
        $this->assertNull($customer->mikrotik_username);
        $this->assertNull($customer->mikrotik_password);
        $this->assertDatabaseMissing('subscriptions', [
            'customer_id' => $customer->id,
        ]);
    }

    public function test_party_inline_update_supports_name_and_phone_fields(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Live Inline Party',
            'phone' => '01710101010',
            'connection_id' => 'INLINE-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);

        $this->actingAs($user)->patchJson(route('customers.inline-update', $customer), [
            'field' => 'name',
            'value' => 'Live Inline Updated',
        ])->assertOk()->assertJsonPath('value', 'Live Inline Updated');

        $this->actingAs($user)->patchJson(route('customers.inline-update', $customer), [
            'field' => 'phone',
            'value' => '01820202020',
        ])->assertOk()->assertJsonPath('value', '01820202020');

        $customer->refresh();
        $this->assertSame('Live Inline Updated', $customer->name);
        $this->assertSame('01820202020', $customer->phone);
    }

    public function test_balance_cell_links_to_the_party_ledger(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Ledger Link Party',
            'phone' => '01710101012',
            'connection_id' => 'LEDGER-1',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);

        $ledgerUrl = route('accounting.ledger', ['customer_id' => $customer->id]);

        $this->actingAs($user)->get(route('customers.index'))
            ->assertOk()
            ->assertSee($ledgerUrl, false);

        // The party details page links back to that party's ledger.
        $this->actingAs($user)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee($ledgerUrl, false);
    }

    public function test_party_note_is_inline_editable_and_shown_on_both_party_lists(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Noted Party',
            'phone' => '01710101011',
            'connection_id' => 'NOTE-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);

        $this->actingAs($user)->patchJson(route('customers.inline-update', $customer), [
            'field' => 'notes',
            'value' => 'Called about relocation on 12 Aug',
        ])->assertOk()->assertJsonPath('value', 'Called about relocation on 12 Aug');

        $this->assertSame('Called about relocation on 12 Aug', $customer->refresh()->notes);

        $this->actingAs($user)->get(route('customers.index'))
            ->assertOk()
            ->assertSee('<th>Note</th>', false)
            ->assertSee('Called about relocation on 12 Aug');
    }

    public function test_deleted_party_note_and_name_are_inline_editable(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Trashed Party',
            'phone' => '01710101012',
            'connection_id' => 'NOTE-DEL',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);
        $customer->delete();
        $this->assertTrue($customer->fresh()->trashed());

        // The Deleted Parties list reuses the same inline editors.
        $this->actingAs($user)->get(route('customers.deleted'))
            ->assertOk()
            ->assertSee('<th>Note</th>', false)
            ->assertSee('data-inline-field="notes"', false)
            ->assertSee('data-inline-field="name"', false);

        $this->actingAs($user)->patchJson(route('customers.inline-update', $customer->id), [
            'field' => 'notes',
            'value' => 'Do not restore - duplicate',
        ])->assertOk()->assertJsonPath('value', 'Do not restore - duplicate');

        $this->actingAs($user)->patchJson(route('customers.inline-update', $customer->id), [
            'field' => 'name',
            'value' => 'Trashed Party (dupe)',
        ])->assertOk();

        $fresh = Customer::withTrashed()->find($customer->id);
        $this->assertSame('Do not restore - duplicate', $fresh->notes);
        $this->assertSame('Trashed Party (dupe)', $fresh->name);
    }

    public function test_party_inline_update_changes_active_package(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $packageOne = InternetPackage::create([
            'name' => 'Starter',
            'speed' => '10 Mbps',
            'monthly_price' => 500,
            'status' => 'active',
        ]);
        $packageTwo = InternetPackage::create([
            'name' => 'Pro',
            'speed' => '20 Mbps',
            'monthly_price' => 800,
            'status' => 'active',
        ]);
        $customer = Customer::create([
            'name' => 'Package Inline Party',
            'phone' => '01722222222',
            'connection_id' => 'INLINE-PKG',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);
        $subscription = Subscription::create([
            'customer_id' => $customer->id,
            'internet_package_id' => $packageOne->id,
            'start_date' => '2026-07-01',
            'status' => 'active',
        ]);
        $customer->update([
            'learned_ip_address' => '10.10.0.25',
            'learned_ip_package_id' => $packageOne->id,
            'last_connected_ip' => '10.10.0.25',
            'last_connected_at' => now(),
        ]);

        $this->actingAs($user)->patchJson(route('customers.inline-update', $customer), [
            'field' => 'package',
            'value' => (string) $packageTwo->id,
        ])->assertOk()->assertJsonPath('value', $packageTwo->name);

        $subscription->refresh();
        $this->assertSame($packageTwo->id, $subscription->internet_package_id);
        $this->assertSame('active', $subscription->status);
        $this->assertNull($subscription->end_date);
        $customer->refresh();
        $this->assertNull($customer->learned_ip_address);
        $this->assertNull($customer->learned_ip_package_id);
        $this->assertNull($customer->last_connected_ip);
        $this->assertNull($customer->last_connected_at);
    }

    public function test_parties_list_separates_ip_assignment_from_last_connected_ip(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        Customer::create([
            'name' => 'Dynamic IP Party',
            'phone' => '01710000101',
            'connection_id' => 'DYNAMIC-IP-USER',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'use_fixed_ip' => false,
            'last_connected_ip' => '10.55.0.25',
        ]);
        Customer::create([
            'name' => 'Fixed IP Party',
            'phone' => '01710000102',
            'connection_id' => 'FIXED-IP-USER',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'use_fixed_ip' => true,
            'fixed_ip_address' => '10.66.0.10',
            'last_connected_ip' => '10.66.0.99',
        ]);

        $this->actingAs($user)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('DYNAMIC-IP-USER')
            ->assertSee('10.55.0.25')
            ->assertSee('Dynamic (profile pool)')
            ->assertSee('Last connected IP')
            ->assertSee('FIXED-IP-USER')
            ->assertSee('10.66.0.10')
            ->assertSee('10.66.0.99');
    }

    public function test_parties_list_shows_the_olt_onu_and_optical_power_next_to_the_name(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        // matched on the ONU serial / MAC
        OltOnu::create([
            'olt_name' => 'Kushtia-OLT-1', 'pon_port' => 3, 'onu_id' => 12, 'status' => 'online',
            'mac_address' => '00:8d:ff:02:2a:17', 'rx_power_dbm' => -21.40, 'tx_power_dbm' => 2.10,
            'port_vlans' => [['port' => 1, 'vlan' => 842]],
        ]);
        Customer::create([
            'name' => 'Serial Match Party', 'phone' => '01710000201', 'connection_id' => 'ONU-SERIAL',
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
            'last_connected_mac' => '00:8D:FF:02:2A:17',
        ]);

        // matched on a learned MAC entry
        OltOnu::create([
            'olt_name' => 'Kushtia-OLT-2', 'pon_port' => 1, 'onu_id' => 4, 'status' => 'online',
            'mac_address' => 'aa:bb:cc:dd:ee:ff', 'rx_power_dbm' => -27.90,
            'learned_macs' => [['mac' => 'e4:8d:8c:11:22:33', 'vlan' => 100]],
        ]);
        Customer::create([
            'name' => 'Learned Match Party', 'phone' => '01710000202', 'connection_id' => 'ONU-LEARNED',
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
            'last_connected_mac' => 'E4:8D:8C:11:22:33',
        ]);

        Customer::create([
            'name' => 'No ONU Party', 'phone' => '01710000203', 'connection_id' => 'NO-ONU',
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);

        $body = $this->actingAs($user)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Kushtia-OLT-1')
            ->assertSee('3/12')
            ->assertSee('VLAN 842')
            ->assertSee('-21.40')
            ->assertSee('2.10')
            ->assertSee('Kushtia-OLT-2')
            ->assertSee('1/4')
            ->assertSee('VLAN 100')
            ->assertSee('-27.90')
            ->assertSee('onu-sub', false)
            ->assertSee('onu-pwr', false)
            ->getContent();

        // OLT / ONU + VLAN sit under the name; Rx / Tx sit under the phone
        $this->assertMatchesRegularExpression('#Serial Match Party</span>\s*<div class="onu-sub">#s', $body);
        $this->assertMatchesRegularExpression('#01710000201</span>\s*<div class="onu-pwr">#s', $body);
        // -27.90 (weaker than -25) is red, -21.40 (-25..-15) is green
        $this->assertMatchesRegularExpression('#badge failed">-27\.90#', $body);
        $this->assertMatchesRegularExpression('#badge active">-21\.40#', $body);
    }

    public function test_parties_list_prefers_a_learned_device_mac_over_a_conflicting_onu_serial(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        OltOnu::create([
            'olt_name' => 'US_GPON', 'pon_port' => 3, 'onu_id' => 18, 'status' => 'online',
            'mac_address' => '5c:62:8b:b6:00:39', 'rx_power_dbm' => -17.77,
            'port_vlans' => [['port' => 1, 'vlan' => 43]],
            'last_live_polled_at' => now(),
        ]);
        OltOnu::create([
            'olt_name' => 'US_EPON', 'pon_port' => 3, 'onu_id' => 13, 'status' => 'online',
            'mac_address' => '00:d3:9e:76:7f:20', 'rx_power_dbm' => -22.29,
            'learned_macs' => [['mac' => '5c:62:8b:b6:00:39', 'vlan' => 21]],
            'last_live_polled_at' => now()->subHour(),
        ]);
        Customer::create([
            'name' => 'Shohan Saddam', 'phone' => 'Not provided', 'connection_id' => 'shohan_saddam',
            'mikrotik_username' => 'shohan_saddam', 'address' => 'Kushtia', 'status' => 'active',
            'is_customer' => true, 'last_connected_mac' => '5C:62:8B:B6:00:39',
        ]);

        $this->actingAs($user)
            ->get(route('customers.index', ['search' => 'shohan_saddam']))
            ->assertOk()
            ->assertSee('US_EPON')
            ->assertSee('3/13')
            ->assertSee('VLAN 21')
            ->assertDontSee('US_GPON')
            ->assertDontSee('VLAN 43');
    }

    public function test_customer_show_activity_table_lists_the_admin_who_took_a_payment(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $paidByAdmin = User::factory()->create(['name' => 'Rokeya Collector']);

        $package = InternetPackage::create([
            'name' => 'Activity Package',
            'speed' => '20 Mbps',
            'monthly_price' => 500,
            'status' => 'active',
        ]);
        $customer = Customer::create([
            'name' => 'Activity Party',
            'phone' => '01755555555',
            'connection_id' => 'ACT-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'notes' => '[27/08/2026 14:30] Bulk invoice INV-ACT-1 paid: 1 Month, 27/08/2026 to 26/09/2026, amount 500.00, reference BULK-41BB5ED856F8.',
        ]);
        Subscription::create([
            'customer_id' => $customer->id,
            'internet_package_id' => $package->id,
            'start_date' => '2026-08-27',
            'status' => 'active',
        ]);
        $invoice = Invoice::create([
            'entry_by' => $paidByAdmin->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-ACT-1',
            'billing_month' => '2026-08',
            'invoice_type' => 'service',
            'subtotal' => 500, 'discount' => 0, 'vat' => 0, 'total' => 500,
            'paid_amount' => 500, 'due_amount' => 0, 'status' => 'paid',
            'due_date' => '2026-08-27',
        ]);
        $invoice->payments()->create([
            'entry_by' => $paidByAdmin->id,
            'customer_id' => $customer->id,
            'amount' => 500,
            'payment_method' => 'cash',
            'payment_date' => '2026-08-27',
        ]);

        $this->actingAs($user)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Party activity &amp; concession log', false)
            ->assertSee('Payment received')
            ->assertSee('BULK-41BB5ED856F8')
            ->assertSee('Rokeya Collector');
    }

    public function test_customer_show_displays_mikrotik_comment_from_imported_secret(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $router = MikrotikRouter::create([
            'name' => 'Profile Router',
            'ip_address' => '10.10.10.100',
            'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 60,
            'inactive_pppoe_profile' => 'inactive',
            'username' => 'admin',
            'password' => 'secret',
            'status' => 'active',
        ]);
        $customer = Customer::create([
            'name' => 'Comment Customer',
            'phone' => '01733333333',
            'connection_id' => 'COM-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);
        MikrotikImportedSecret::create([
            'mikrotik_router_id' => $router->id,
            'customer_id' => $customer->id,
            'routeros_id' => '*ROUTE-1',
            'name' => $customer->connection_id,
            'password' => 'secret',
            'service' => 'pppoe',
            'disabled' => false,
            'router_comment' => 'Imported comment only',
            'imported_at' => now(),
        ]);

        $this->actingAs($user)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('MikroTik comment')
            ->assertSee('Imported comment only');
    }

    public function test_customer_show_has_inline_quick_recharge_form_before_the_activity_log(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Recharge Customer', 'phone' => '01733333340', 'connection_id' => 'RC-001',
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);

        $body = $this->actingAs($user)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Quick Recharge')
            ->assertSee('Recharge now')
            ->assertSee('action="'.route('customers.payments.store', $customer).'"', false)
            ->getContent();

        $this->assertLessThan(
            strpos($body, 'Party activity &amp; concession log'),
            strpos($body, 'id="quick-recharge"'),
            'Quick Recharge should render before the party activity log.'
        );
    }

    public function test_connection_id_is_required_when_assigning_internet_package(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $package = InternetPackage::create([
            'name' => 'Home Basic',
            'speed' => '20 Mbps',
            'mikrotik_profile' => 'Home Basic',
            'monthly_price' => 1000,
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('customers.store'), [
            'name' => 'ISP Buyer',
            'phone' => '01722222222',
            'connection_id' => '',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => '1',
            'internet_package_id' => $package->id,
            'start_date' => '2026-06-02',
        ])->assertSessionHasErrors('connection_id');
    }

    public function test_active_customer_without_paid_month_can_be_activated_until_next_month_date(): void
    {
        Carbon::setTestNow('2026-08-11 10:00:00');

        try {
            $user = User::factory()->create();
            $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
            $user->permissions()->attach(
                Permission::whereIn('name', ['grant_grace_period', 'override_service_validity', 'quick_activate_service', 'force_service_status', 'mark_special_customer'])->pluck('id')
            );
            $package = InternetPackage::create([
                'name' => 'Quick Activate',
                'speed' => '15 Mbps',
                'mikrotik_profile' => 'Quick Activate',
                'monthly_price' => 1000,
                'status' => 'active',
            ]);

            $customer = Customer::create([
                'name' => 'Quick Activation Customer',
                'phone' => '01788888888',
                'connection_id' => 'QACT-001',
                'address' => 'Kushtia',
                'status' => 'active',
                'is_customer' => true,
            ]);
            $this->makeImportedSecretForCustomer($customer, 'QACT-001');

            $subscription = Subscription::create([
                'customer_id' => $customer->id,
                'internet_package_id' => $package->id,
                'start_date' => '2026-07-01',
                'status' => 'active',
            ]);

            $this->actingAs($user)
                ->from(route('customers.index'))
                ->post(route('customers.activate-next-date', $customer))
                ->assertRedirect(route('customers.index'))
                ->assertSessionHasNoErrors();

            $customer->refresh();
            $subscription->refresh();

            $this->assertSame('active', $customer->status);
            $this->assertSame('2026-09-11', $customer->service_valid_until?->format('Y-m-d'));
            $this->assertSame('2026-08-11', $customer->service_valid_from?->format('Y-m-d'));
            $this->assertSame('active', $subscription->status);
            $this->assertNull($subscription->end_date);
            $this->assertNull($customer->grace_used_at);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_active_customer_without_paid_month_can_be_activated_until_selected_date(): void
    {
        Carbon::setTestNow('2026-08-11 10:00:00');

        try {
            $user = User::factory()->create();
            $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
            $user->permissions()->attach(
                Permission::whereIn('name', ['grant_grace_period', 'override_service_validity', 'quick_activate_service', 'force_service_status', 'mark_special_customer'])->pluck('id')
            );
            $package = InternetPackage::create([
                'name' => 'Quick Activate Custom',
                'speed' => '15 Mbps',
                'mikrotik_profile' => 'Quick Activate Custom',
                'monthly_price' => 1000,
                'status' => 'active',
            ]);

            $customer = Customer::create([
                'name' => 'Quick Activation With Custom Date',
                'phone' => '01788999999',
                'connection_id' => 'QACT-002',
                'address' => 'Kushtia',
                'status' => 'active',
                'is_customer' => true,
            ]);
            $this->makeImportedSecretForCustomer($customer, 'QACT-002');

            Subscription::create([
                'customer_id' => $customer->id,
                'internet_package_id' => $package->id,
                'start_date' => '2026-07-01',
                'status' => 'active',
            ]);

            $this->actingAs($user)
                ->from(route('customers.index'))
                ->post(route('customers.activate-next-date', $customer), ['active_until' => '2026-10-01'])
                ->assertRedirect(route('customers.index'))
                ->assertSessionHasNoErrors();

            $customer->refresh();

            $this->assertSame('2026-10-01', $customer->service_valid_until?->format('Y-m-d'));
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_inactive_customer_with_no_paid_month_shows_activate_action(): void
    {
        Carbon::setTestNow('2026-08-11 10:00:00');

        try {
            $user = User::factory()->create();
            $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
            $user->permissions()->attach(
                Permission::whereIn('name', ['grant_grace_period', 'override_service_validity', 'quick_activate_service', 'force_service_status', 'mark_special_customer'])->pluck('id')
            );
            $package = InternetPackage::create([
                'name' => 'Inactive Activate',
                'speed' => '10 Mbps',
                'mikrotik_profile' => 'Inactive Activate',
                'monthly_price' => 1000,
                'status' => 'active',
            ]);

            $customer = Customer::create([
                'name' => 'Inactive Quick Activation',
                'phone' => '01712121212',
                'connection_id' => 'IACT-001',
                'address' => 'Kushtia',
                'status' => 'inactive',
                'is_customer' => true,
            ]);
            $this->makeImportedSecretForCustomer($customer, 'IACT-001');
            Subscription::create([
                'customer_id' => $customer->id,
                'internet_package_id' => $package->id,
                'start_date' => '2026-07-01',
                'status' => 'inactive',
            ]);

            $this->actingAs($user)->get(route('customers.index'))
                ->assertOk()
                ->assertSee('Activate until');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_non_imported_customer_with_no_paid_month_does_not_show_activate_action(): void
    {
        Carbon::setTestNow('2026-08-11 10:00:00');

        try {
            $user = User::factory()->create();
            $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
            $package = InternetPackage::create([
                'name' => 'Inactive Non Imported',
                'speed' => '10 Mbps',
                'mikrotik_profile' => 'Inactive Non Imported',
                'monthly_price' => 1000,
                'status' => 'active',
            ]);

            $customer = Customer::create([
                'name' => 'Non-Imported Quick Activation',
                'phone' => '01713131313',
                'connection_id' => 'NIACT-001',
                'address' => 'Kushtia',
                'status' => 'inactive',
                'is_customer' => true,
            ]);
            Subscription::create([
                'customer_id' => $customer->id,
                'internet_package_id' => $package->id,
                'start_date' => '2026-07-01',
                'status' => 'inactive',
            ]);

            $this->actingAs($user)->get(route('customers.index'))
                ->assertOk()
                ->assertDontSee('Activate until 2026-09-11')
                ->assertDontSee('name="active_until"');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_imported_customer_with_non_service_invoice_still_follows_normal_flow(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $package = InternetPackage::create([
            'name' => 'Imported With Invoice',
            'speed' => '12 Mbps',
            'mikrotik_profile' => 'Imported With Invoice',
            'monthly_price' => 500,
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'name' => 'Imported customer with invoice',
            'phone' => '01714141414',
            'connection_id' => 'IMP-INV-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);
        $this->makeImportedSecretForCustomer($customer, 'IMP-INV-001');

        Subscription::create([
            'customer_id' => $customer->id,
            'internet_package_id' => $package->id,
            'start_date' => '2026-07-01',
            'status' => 'active',
        ]);

        Invoice::create([
            'customer_id' => $customer->id,
            'invoice_no' => 'INV-IMP-INV-001',
            'billing_month' => '2026-08',
            'invoice_type' => 'other',
            'subtotal' => 0,
            'discount' => 0,
            'vat' => 0,
            'total' => 0,
            'paid_amount' => 0,
            'due_amount' => 0,
            'status' => 'paid',
            'due_date' => now()->toDateString(),
        ]);

        $this->actingAs($user)->get(route('customers.index'))
            ->assertOk()
            ->assertDontSee('Activate until 2026-09-11')
            ->assertDontSee('name="active_until"');
    }

    public function test_quick_activation_without_package_returns_error(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $user->permissions()->attach(
            Permission::whereIn('name', ['grant_grace_period', 'override_service_validity', 'quick_activate_service', 'force_service_status', 'mark_special_customer'])->pluck('id')
        );
        $customer = Customer::create([
            'name' => 'No package customer',
            'phone' => '01799999999',
            'connection_id' => 'NOPKG-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);

        $this->actingAs($user)
            ->post(route('customers.activate-next-date', $customer))
            ->assertSessionHasErrors('active_until');
    }

    public function test_expired_paid_period_shows_elapsed_days_and_null_due_date_is_disabled(): void
    {
        Carbon::setTestNow('2026-06-20 12:00:00');

        try {
            $user = User::factory()->create();
            $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
            $user->permissions()->attach(
                Permission::whereIn('name', ['grant_grace_period', 'override_service_validity', 'quick_activate_service', 'force_service_status', 'mark_special_customer'])->pluck('id')
            );
            $customer = Customer::create([
                'name' => 'Expired Customer',
                'phone' => '01733333333',
                'connection_id' => 'EXPIRED-001',
                'address' => 'Kushtia',
                'status' => 'active',
                'is_customer' => true,
            ]);
            Invoice::create([
                'customer_id' => $customer->id,
                'invoice_no' => 'INV-EXPIRED-001',
                'billing_month' => '2026-06',
                'invoice_type' => 'service',
                'subtotal' => 1000,
                'discount' => 0,
                'vat' => 0,
                'total' => 1000,
                'paid_amount' => 0,
                'due_amount' => 1000,
                'status' => 'unpaid',
                'due_date' => null,
            ]);

            $this->assertSame(-20, $customer->activeDaysRemaining());
            $this->actingAs($user)->get(route('customers.index'))
                ->assertOk()
                ->assertSee('expired 20d')
                ->assertSee('Assign package for grace');

            Artisan::call('billing:disable-overdue-customers', ['--date' => '2026-06-20']);

            $this->assertSame('inactive', $customer->refresh()->status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_expired_active_customer_with_package_can_receive_grace_period(): void
    {
        Carbon::setTestNow('2026-06-20 12:00:00');

        try {
            $user = User::factory()->create();
            $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
            $user->permissions()->attach(
                Permission::whereIn('name', ['grant_grace_period', 'override_service_validity', 'quick_activate_service', 'force_service_status', 'mark_special_customer'])->pluck('id')
            );
            $package = InternetPackage::create([
                'name' => 'Grace Package',
                'speed' => '20 Mbps',
                'mikrotik_profile' => 'Grace Package',
                'monthly_price' => 1000,
                'status' => 'active',
            ]);
            $customer = Customer::create([
                'name' => 'Grace Customer',
                'phone' => '01744444444',
                'connection_id' => 'GRACE-001',
                'address' => 'Kushtia',
                'status' => 'active',
                'is_customer' => true,
            ]);
            Subscription::create([
                'customer_id' => $customer->id,
                'internet_package_id' => $package->id,
                'start_date' => '2026-05-01',
                'status' => 'active',
            ]);
            Invoice::create([
                'customer_id' => $customer->id,
                'invoice_no' => 'INV-GRACE-001',
                'billing_month' => '2026-06',
                'invoice_type' => 'service',
                'subtotal' => 1000,
                'discount' => 0,
                'vat' => 0,
                'total' => 1000,
                'paid_amount' => 0,
                'due_amount' => 1000,
                'status' => 'unpaid',
            ]);

            $this->actingAs($user)
                ->from(route('customers.index'))
                ->post(route('customers.grace-period', $customer), ['grace_days' => 3])
                ->assertRedirect(route('customers.index'))
                ->assertSessionHasNoErrors();

            $customer->refresh();
            $this->assertSame('active', $customer->status);
            $this->assertSame(3, $customer->grace_days);
            $this->assertSame('2026-06-23', $customer->grace_until->format('Y-m-d'));
            $this->assertNotNull($customer->grace_used_at);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_manual_validity_change_requires_and_records_a_reason(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $user->permissions()->attach(
            Permission::whereIn('name', ['grant_grace_period', 'override_service_validity', 'quick_activate_service', 'force_service_status', 'mark_special_customer'])->pluck('id')
        );
        $customer = Customer::create([
            'name' => 'Validity Customer',
            'phone' => '01755555555',
            'connection_id' => 'VALID-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'service_valid_until' => '2026-06-30',
        ]);

        $this->actingAs($user)
            ->post(route('customers.service-validity.update', $customer), ['service_valid_until' => '2026-07-15'])
            ->assertSessionHasErrors('validity_note');

        $this->actingAs($user)
            ->post(route('customers.service-validity.update', $customer), [
                'service_valid_until' => '2026-07-15',
                'validity_note' => 'Customer paid late; manager approved extension.',
            ])
            ->assertRedirect();

        $customer->refresh();
        $this->assertSame('2026-07-15', $customer->service_valid_until?->format('Y-m-d'));
        $this->assertStringContainsString('30/06/2026 → 15/07/2026', $customer->service_validity_note);
        $this->assertStringContainsString('manager approved extension', $customer->notes);
    }

    public function test_profile_shows_who_entered_the_party_and_who_took_each_note_action(): void
    {
        $admin = User::factory()->create(['name' => 'Rakib Admin']);
        $admin->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $admin->permissions()->attach(
            Permission::whereIn('name', ['override_service_validity', 'force_service_status'])->pluck('id')
        );

        $customer = $this->actingAs($admin)->post(route('customers.store'), [
            'name' => 'Log Party', 'phone' => '01700000123', 'connection_id' => 'LOG-1',
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => '1',
            'service_valid_until' => '2026-06-30',
        ])->isRedirect() ? Customer::where('connection_id', 'LOG-1')->firstOrFail() : null;
        $this->assertNotNull($customer);
        $this->assertSame((string) $admin->id, $customer->entry_by);

        $this->actingAs($admin)->post(route('customers.service-validity.update', $customer), [
            'service_valid_until' => '2026-07-15',
            'validity_note' => 'Manager approved a late extension.',
        ])->assertRedirect();

        $this->assertStringContainsString('by Rakib Admin', $customer->refresh()->notes);

        $this->actingAs($admin)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Entered by')
            ->assertSee('Rakib Admin');
    }

    public function test_manual_past_validity_date_expires_subscription_for_mikrotik_sync(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $user->permissions()->attach(
            Permission::whereIn('name', ['grant_grace_period', 'override_service_validity', 'quick_activate_service', 'force_service_status', 'mark_special_customer'])->pluck('id')
        );
        $package = InternetPackage::create(['name' => 'Expired Plan', 'speed' => '10 Mbps', 'monthly_price' => 500, 'status' => 'active']);
        $customer = Customer::create(['name' => 'Expiry Customer', 'phone' => '01766666666', 'connection_id' => 'EXPIRE-001', 'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true]);
        $subscription = Subscription::create(['customer_id' => $customer->id, 'internet_package_id' => $package->id, 'start_date' => '2026-06-01', 'status' => 'active']);

        $this->actingAs($user)->post(route('customers.service-validity.update', $customer), [
            'service_valid_until' => now()->subDay()->toDateString(),
            'validity_note' => 'Payment period is over.',
        ])->assertRedirect();

        $this->assertSame('inactive', $customer->refresh()->status);
        $this->assertSame('inactive', $subscription->refresh()->status);
        $this->assertSame(now()->subDay()->toDateString(), $subscription->end_date?->format('Y-m-d'));
    }

    public function test_customer_can_be_assigned_and_synced_to_multiple_mikrotik_targets(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
        $customer = Customer::create([
            'name' => 'Multi Router Customer',
            'phone' => '01760000001',
            'connection_id' => 'MULTI-001',
            'mikrotik_username' => 'MULTI-001',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
        ]);
        $routerOne = MikrotikRouter::create([
            'name' => 'Router 1036',
            'ip_address' => '103.133.200.180',
            'api_port' => 8787,
            'username' => 'admin',
            'password' => 'secret',
            'status' => 'active',
        ]);
        $routerTwo = MikrotikRouter::create([
            'name' => 'Router 1037',
            'ip_address' => '103.133.200.181',
            'api_port' => 8787,
            'username' => 'admin',
            'password' => 'secret',
            'status' => 'active',
        ]);
        $syncService = \Mockery::mock(MikrotikCustomerSyncService::class);
        $syncService->shouldReceive('sync')->once()->andReturn('synced');
        $this->app->instance(MikrotikCustomerSyncService::class, $syncService);

        $this->actingAs($user)->post(route('customers.mikrotik-targets.update', $customer), [
            'mikrotik_router_ids' => [$routerOne->id, $routerTwo->id],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertSame($routerOne->id, $customer->refresh()->mikrotik_router_id);
        $this->assertEqualsCanonicalizing(
            [$routerOne->id, $routerTwo->id],
            $customer->mikrotikRouters()->pluck('mikrotik_routers.id')->all()
        );

        $this->actingAs($user)->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee('Router 1036')
            ->assertSee('Router 1037')
            ->assertSee('Save &amp; sync targets', false);
    }

    public function test_force_inactive_keeps_validity_and_inactivates_the_subscription(): void
    {
        Carbon::setTestNow('2026-08-12 10:00:00');

        try {
            $user = User::factory()->create();
            $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
            $user->permissions()->attach(
                Permission::whereIn('name', ['grant_grace_period', 'override_service_validity', 'quick_activate_service', 'force_service_status', 'mark_special_customer'])->pluck('id')
            );
            $package = InternetPackage::create(['name' => 'Force Plan', 'speed' => '10 Mbps', 'monthly_price' => 500, 'status' => 'active']);
            $customer = Customer::create([
                'name' => 'Force Inactive Customer',
                'phone' => '01760000002',
                'connection_id' => 'FORCE-001',
                'mikrotik_username' => 'FORCE-001',
                'address' => 'Kushtia',
                'status' => 'active',
                'is_customer' => true,
                'service_valid_until' => '2026-09-10',
                'grace_until' => '2026-08-20',
                'grace_days' => 8,
            ]);
            $subscription = Subscription::create([
                'customer_id' => $customer->id,
                'internet_package_id' => $package->id,
                'start_date' => '2026-08-01',
                'status' => 'active',
            ]);
            $syncService = \Mockery::mock(MikrotikCustomerSyncService::class);
            $syncService->shouldReceive('sync')->once()->andReturn('moved_inactive');
            $this->app->instance(MikrotikCustomerSyncService::class, $syncService);

            $this->actingAs($user)->post(route('customers.force-inactive', $customer), [
                'inactive_note' => 'Customer requested a temporary line suspension.',
            ])->assertRedirect()->assertSessionHasNoErrors();

            $customer->refresh();
            $subscription->refresh();
            $this->assertSame('inactive', $customer->status);
            $this->assertSame('2026-09-10', $customer->service_valid_until?->format('Y-m-d'));
            $this->assertSame('2026-08-20', $customer->grace_until?->format('Y-m-d'));
            $this->assertSame(8, $customer->grace_days);
            $this->assertSame('inactive', $subscription->status);
            $this->assertSame('2026-08-12', $subscription->end_date?->format('Y-m-d'));
            $this->assertStringContainsString('temporary line suspension', $customer->notes);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_temporary_active_reverses_inactive_without_changing_validity_or_grace(): void
    {
        Carbon::setTestNow('2026-08-12 10:00:00');

        try {
            $user = User::factory()->create();
            $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
            $user->permissions()->attach(
                Permission::whereIn('name', ['grant_grace_period', 'override_service_validity', 'quick_activate_service', 'force_service_status', 'mark_special_customer'])->pluck('id')
            );
            $package = InternetPackage::create(['name' => 'Temporary Plan', 'speed' => '20 Mbps', 'monthly_price' => 700, 'status' => 'active']);
            $customer = Customer::create([
                'name' => 'Temporary Active Customer',
                'phone' => '01760000003',
                'connection_id' => 'TEMP-001',
                'mikrotik_username' => 'TEMP-001',
                'address' => 'Kushtia',
                'status' => 'inactive',
                'is_customer' => true,
                'service_valid_until' => '2026-09-10',
                'grace_until' => '2026-08-20',
                'grace_days' => 8,
            ]);
            $subscription = Subscription::create([
                'customer_id' => $customer->id,
                'internet_package_id' => $package->id,
                'start_date' => '2026-08-01',
                'end_date' => '2026-08-12',
                'status' => 'inactive',
            ]);
            $syncService = \Mockery::mock(MikrotikCustomerSyncService::class);
            $syncService->shouldReceive('sync')->once()->andReturn('updated');
            $this->app->instance(MikrotikCustomerSyncService::class, $syncService);

            $this->actingAs($user)->get(route('customers.show', $customer))
                ->assertOk()
                ->assertSee('Temporary active');

            $this->actingAs($user)->post(route('customers.force-active', $customer), [
                'active_note' => 'Manager approved a temporary reconnection.',
            ])->assertRedirect()->assertSessionHasNoErrors();

            $customer->refresh();
            $subscription->refresh();
            $this->assertSame('active', $customer->status);
            $this->assertSame('2026-09-10', $customer->service_valid_until?->format('Y-m-d'));
            $this->assertSame('2026-08-20', $customer->grace_until?->format('Y-m-d'));
            $this->assertSame(8, $customer->grace_days);
            $this->assertSame('active', $subscription->status);
            $this->assertNull($subscription->end_date);
            $this->assertStringContainsString('temporary reconnection', $customer->notes);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_expired_party_payment_renews_the_remembered_package(): void
    {
        Carbon::setTestNow('2026-07-18 10:00:00');

        try {
            $user = User::factory()->create();
            $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
            $package = InternetPackage::create(['name' => 'Remembered 30 MB', 'speed' => '30 Mbps', 'mikrotik_profile' => 'Remembered 30 MB', 'monthly_price' => 1000, 'status' => 'active']);
            $customer = Customer::create(['name' => 'Renew Customer', 'phone' => '01777777777', 'connection_id' => 'RENEW-001', 'address' => 'Kushtia', 'status' => 'inactive', 'is_customer' => true]);
            $subscription = Subscription::create(['customer_id' => $customer->id, 'internet_package_id' => $package->id, 'start_date' => '2026-06-01', 'end_date' => '2026-07-01', 'status' => 'inactive']);

            $this->actingAs($user)->post(route('customers.payments.store', $customer), [
                'amount' => 1000,
                'payment_method' => 'cash',
                'payment_date' => '2026-07-18',
            ])->assertRedirect(route('customers.show', $customer));

            $this->assertSame('active', $customer->refresh()->status);
            $this->assertSame('active', $subscription->refresh()->status);
            $this->assertNull($subscription->end_date);
            $this->assertDatabaseHas('invoices', ['customer_id' => $customer->id, 'billing_month' => '2026-07', 'due_amount' => 0]);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_inactive_customer_with_no_paid_month_and_used_grace_still_shows_activate_button(): void
    {
        Carbon::setTestNow('2026-08-11 10:00:00');

        try {
            $user = User::factory()->create();
            $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
            $user->permissions()->attach(
                Permission::whereIn('name', ['grant_grace_period', 'override_service_validity', 'quick_activate_service', 'force_service_status', 'mark_special_customer'])->pluck('id')
            );
            $package = InternetPackage::create([
                'name' => 'Activate after used grace',
                'speed' => '25 Mbps',
                'mikrotik_profile' => 'Activate after used grace',
                'monthly_price' => 500,
                'status' => 'active',
            ]);

            $customer = Customer::create([
                'name' => 'No paid month used grace',
                'phone' => '01890000001',
                'connection_id' => 'NO-PAID-GRACE',
                'address' => 'Kushtia',
                'status' => 'inactive',
                'is_customer' => true,
                'grace_used_at' => '2026-08-01 10:00:00',
            ]);
            $this->makeImportedSecretForCustomer($customer, 'NO-PAID-GRACE');

            Subscription::create([
                'customer_id' => $customer->id,
                'internet_package_id' => $package->id,
                'start_date' => '2026-07-01',
                'status' => 'inactive',
            ]);

            $this->actingAs($user)
                ->get(route('customers.index'))
                ->assertOk()
                ->assertSee('No paid month')
                ->assertSee('Activate until')
                ->assertSee('Grace already used');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_special_isp_customer_has_no_visible_or_filterable_validity_date(): void
    {
        Carbon::setTestNow('2026-08-27 10:00:00');

        try {
            $user = User::factory()->create();
            $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());
            $normalCustomer = Customer::create([
                'name' => 'Normal Expiry Party',
                'phone' => '01710000001',
                'connection_id' => 'NORMAL-EXPIRY',
                'address' => 'Kushtia',
                'status' => 'active',
                'is_customer' => true,
                'service_valid_until' => '2026-08-27',
            ]);
            $specialCustomer = Customer::create([
                'name' => 'Special Expiry Party',
                'phone' => '01710000002',
                'connection_id' => 'SPECIAL-EXPIRY',
                'address' => 'Kushtia',
                'status' => 'active',
                'is_customer' => true,
                'never_suspend' => true,
                'service_valid_until' => '2035-12-31',
            ]);

            $this->actingAs($user)
                ->get(route('customers.index'))
                ->assertOk()
                ->assertViewHas('expirySummary', fn (array $summary) => $summary['today'] === 1)
                ->assertSee($normalCustomer->name)
                ->assertSee($specialCustomer->name)
                ->assertSee('no limit')
                ->assertDontSee('31/12/2035');

            $this->actingAs($user)
                ->get(route('customers.index', ['expiry_window' => 'today']))
                ->assertOk()
                ->assertSee($normalCustomer->name)
                ->assertDontSee($specialCustomer->name);

            $this->actingAs($user)
                ->get(route('customers.show', $specialCustomer))
                ->assertOk()
                ->assertSee('no limit')
                ->assertDontSee('31/12/2035')
                ->assertDontSee('Force validity date');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_inline_connection_id_change_resyncs_mikrotik_and_clears_learned_ip(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        $customer = Customer::create([
            'name' => 'Inline Conn Party',
            'phone' => '01733333333',
            'connection_id' => 'OLD-CONN',
            'mikrotik_username' => 'OLD-CONN',
            'address' => 'Kushtia',
            'status' => 'active',
            'is_customer' => true,
            'learned_ip_address' => '10.5.0.9',
            'last_connected_ip' => '10.5.0.9',
            'last_connected_at' => now(),
        ]);

        $syncService = \Mockery::mock(MikrotikCustomerSyncService::class);
        $syncService->shouldReceive('sync')->once()->andReturn('updated');
        $this->app->instance(MikrotikCustomerSyncService::class, $syncService);

        $this->actingAs($user)->patchJson(route('customers.inline-update', $customer), [
            'field' => 'connection_id',
            'value' => 'NEW-CONN',
        ])->assertOk()->assertJsonPath('value', 'NEW-CONN');

        $customer->refresh();
        $this->assertSame('NEW-CONN', $customer->mikrotik_username);
        $this->assertNull($customer->learned_ip_address);
        $this->assertNull($customer->last_connected_ip);
    }

    public function test_inline_connection_id_change_removes_the_secret_left_under_the_old_name(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        $router = MikrotikRouter::create([
            'name' => 'Core', 'ip_address' => '10.6.0.1', 'api_port' => 8728,
            'inactive_pppoe_profile' => 'inactive', 'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);
        $customer = Customer::create([
            'name' => 'Rename Party', 'phone' => '01733333334', 'connection_id' => 'OLD-NAME',
            'mikrotik_username' => 'OLD-NAME', 'mikrotik_router_id' => $router->id,
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);
        $staleRow = MikrotikImportedSecret::create([
            'mikrotik_router_id' => $router->id, 'customer_id' => $customer->id, 'routeros_id' => '*7',
            'name' => 'OLD-NAME', 'password' => 'x', 'service' => 'pppoe', 'disabled' => false, 'imported_at' => now(),
        ]);

        $syncService = \Mockery::mock(MikrotikCustomerSyncService::class);
        $syncService->shouldReceive('sync')->once()->andReturn('updated');
        $syncService->shouldReceive('removeUsername')
            ->once()
            ->withArgs(fn ($party, $username) => $username === 'OLD-NAME')
            ->andReturn('Core: 1 secret(s) removed');
        $this->app->instance(MikrotikCustomerSyncService::class, $syncService);

        $this->actingAs($user)->patchJson(route('customers.inline-update', $customer), [
            'field' => 'connection_id',
            'value' => 'NEW-NAME',
        ])->assertOk();

        $this->assertNull($staleRow->fresh());
    }

    public function test_deleting_a_party_drops_its_imported_secret_row(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        $router = MikrotikRouter::create([
            'name' => 'Core', 'ip_address' => '10.6.0.2', 'api_port' => 8728,
            'inactive_pppoe_profile' => 'inactive', 'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);
        $customer = Customer::create([
            'name' => 'Doomed Party', 'phone' => '01733333335', 'connection_id' => 'DOOMED',
            'mikrotik_username' => 'DOOMED', 'mikrotik_router_id' => $router->id,
            'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);
        $row = MikrotikImportedSecret::create([
            'mikrotik_router_id' => $router->id, 'customer_id' => $customer->id, 'routeros_id' => '*8',
            'name' => 'DOOMED', 'password' => 'x', 'service' => 'pppoe', 'disabled' => false, 'imported_at' => now(),
        ]);

        $syncService = \Mockery::mock(MikrotikCustomerSyncService::class);
        $syncService->shouldReceive('remove')->once()->andReturn('Core: 1 secret(s) removed');
        $this->app->instance(MikrotikCustomerSyncService::class, $syncService);

        $this->actingAs($user)->delete(route('customers.destroy', $customer))->assertRedirect();

        $this->assertNull($row->fresh());
        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_inline_package_change_resyncs_mikrotik(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        $packageOne = InternetPackage::create(['name' => 'P1', 'speed' => '10 Mbps', 'monthly_price' => 500, 'status' => 'active']);
        $packageTwo = InternetPackage::create(['name' => 'P2', 'speed' => '20 Mbps', 'monthly_price' => 900, 'status' => 'active']);
        $customer = Customer::create([
            'name' => 'Inline Pkg Sync Party', 'phone' => '01744444444',
            'connection_id' => 'INLINE-PKG-SYNC', 'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);
        Subscription::create([
            'customer_id' => $customer->id, 'internet_package_id' => $packageOne->id,
            'start_date' => '2026-07-01', 'status' => 'active',
        ]);

        $syncService = \Mockery::mock(MikrotikCustomerSyncService::class);
        $syncService->shouldReceive('sync')->once()->andReturn('updated');
        $this->app->instance(MikrotikCustomerSyncService::class, $syncService);

        $this->actingAs($user)->patchJson(route('customers.inline-update', $customer), [
            'field' => 'package',
            'value' => (string) $packageTwo->id,
        ])->assertOk()->assertJsonPath('value', 'P2');
    }

    public function test_restoring_a_party_recreates_its_mikrotik_secret(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        $customer = Customer::create([
            'name' => 'Restore Sync Party', 'phone' => '01755555555',
            'connection_id' => 'RESTORE-SYNC', 'address' => 'Kushtia', 'status' => 'active', 'is_customer' => true,
        ]);
        $customer->delete();

        $syncService = \Mockery::mock(MikrotikCustomerSyncService::class);
        $syncService->shouldReceive('sync')->once()->andReturn('created');
        $this->app->instance(MikrotikCustomerSyncService::class, $syncService);

        $this->actingAs($user)->post(route('customers.restore', $customer->id))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('success');

        $this->assertFalse($customer->fresh()->trashed());
    }

    public function test_special_isp_flag_can_be_toggled_from_the_party_list(): void
    {
        $user = User::factory()->create();
        foreach (['manage_customers', 'mark_special_customer'] as $name) {
            $user->permissions()->attach(Permission::where('name', $name)->firstOrFail());
        }

        $package = InternetPackage::create([
            'name' => 'Toggle Package', 'speed' => '10 Mbps', 'monthly_price' => 700, 'status' => 'active',
        ]);
        $customer = Customer::create([
            'name' => 'Toggle Party', 'phone' => '01799999999',
            'connection_id' => 'TOGGLE-1', 'address' => 'Kushtia',
            'status' => 'active', 'is_customer' => true, 'never_suspend' => false,
        ]);
        Subscription::create([
            'customer_id' => $customer->id, 'internet_package_id' => $package->id,
            'start_date' => now()->subMonth()->toDateString(), 'status' => 'active',
        ]);

        $this->actingAs($user)->get(route('customers.index'))
            ->assertOk()
            ->assertSee('Set Special');

        $this->actingAs($user)
            ->from(route('customers.index'))
            ->post(route('customers.toggle-special', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertTrue($customer->fresh()->never_suspend);
        $this->assertDatabaseHas('concession_logs', [
            'customer_id' => $customer->id, 'action_type' => 'mark_special',
        ]);

        $this->actingAs($user)
            ->from(route('customers.index'))
            ->post(route('customers.toggle-special', $customer))
            ->assertRedirect(route('customers.index'));

        $this->assertFalse($customer->fresh()->never_suspend);
        $this->assertDatabaseHas('concession_logs', [
            'customer_id' => $customer->id, 'action_type' => 'unmark_special',
        ]);
    }

    public function test_special_toggle_needs_the_mark_special_permission(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        $customer = Customer::create([
            'name' => 'Plain Party', 'phone' => '01788888888',
            'connection_id' => 'PLAIN-1', 'address' => 'Kushtia',
            'status' => 'active', 'is_customer' => true,
        ]);

        $this->actingAs($user)->get(route('customers.index'))
            ->assertOk()
            ->assertDontSee('Set Special');

        $this->actingAs($user)
            ->post(route('customers.toggle-special', $customer))
            ->assertForbidden();

        $this->assertFalse($customer->fresh()->never_suspend);
    }

    public function test_an_expired_but_still_active_party_is_flagged_on_the_list_and_profile(): void
    {
        Carbon::setTestNow('2026-08-29 10:00:00');

        try {
            $user = User::factory()->create();
            $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

            $package = InternetPackage::create(['name' => 'Ovd', 'speed' => '10', 'monthly_price' => 500, 'status' => 'active']);
            $customer = Customer::create([
                'name' => 'Overdue Active Party', 'phone' => '01710000200',
                'connection_id' => 'OVD-1', 'mikrotik_username' => 'OVD-1', 'address' => 'Kushtia',
                'status' => 'active', 'is_customer' => true,
                'service_valid_until' => '2026-08-27',
            ]);
            Subscription::create([
                'customer_id' => $customer->id, 'internet_package_id' => $package->id,
                'start_date' => '2026-07-27', 'status' => 'active',
            ]);

            $this->actingAs($user)->get(route('customers.index'))
                ->assertOk()
                ->assertSee('customer-row-overdue', false)
                ->assertSee('overdue');

            $this->actingAs($user)->get(route('customers.show', $customer))
                ->assertOk()
                ->assertSee('this party is still Active')
                ->assertSee('auto-disable job runs daily');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_dynamic_live_ip_cannot_be_pinned_but_a_fixed_ip_can_be_released(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        $syncService = \Mockery::mock(MikrotikCustomerSyncService::class);
        $syncService->shouldReceive('sync')->andReturn('updated');
        $this->app->instance(MikrotikCustomerSyncService::class, $syncService);

        $customer = Customer::create([
            'name' => 'Auto IP Party', 'phone' => '01710000188',
            'connection_id' => 'AUTO-IP-1', 'mikrotik_username' => 'AUTO-IP-1', 'address' => 'Kushtia',
            'status' => 'active', 'is_customer' => true, 'use_fixed_ip' => false,
            'last_connected_ip' => '10.77.0.42',
        ]);

        $this->actingAs($user)
            ->postJson(route('customers.assign-live-ip', $customer))
            ->assertUnprocessable()
            ->assertJsonPath('message', 'A dynamic session IP cannot be pinned. Reserve an address outside every dynamic pool, then enter it as Fixed IP on the Edit page.');
        $customer->refresh();
        $this->assertFalse((bool) $customer->use_fixed_ip);
        $this->assertNull($customer->fixed_ip_address);

        $customer->update(['use_fixed_ip' => true, 'fixed_ip_address' => '10.77.1.42']);
        $this->actingAs($user)
            ->postJson(route('customers.assign-live-ip', $customer))
            ->assertOk()
            ->assertJson(['is_fixed' => false]);
        $customer->refresh();
        $this->assertFalse((bool) $customer->use_fixed_ip);
        $this->assertNull($customer->fixed_ip_address);
    }

    public function test_manual_fixed_ip_must_be_unique_and_outside_dynamic_pools(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_customers')->firstOrFail());

        Customer::create([
            'name' => 'Holder', 'phone' => '01710000190', 'connection_id' => 'HOLD-1', 'address' => 'x',
            'status' => 'active', 'is_customer' => true, 'use_fixed_ip' => true, 'fixed_ip_address' => '10.88.0.5',
        ]);

        $base = [
            'name' => 'Clash Party', 'phone' => '01710000191', 'connection_id' => 'CLASH-1',
            'address' => 'x', 'status' => 'active', 'is_customer' => '1', 'use_fixed_ip' => '1',
            'fixed_ip_address' => '10.88.0.5',
        ];
        $this->actingAs($user)->post(route('customers.store'), $base)
            ->assertSessionHasErrors('fixed_ip_address');

        $router = MikrotikRouter::create([
            'name' => 'Pool Router', 'ip_address' => '10.0.0.88', 'api_port' => 8728,
            'pppoe_sync_interval_minutes' => 60, 'inactive_pppoe_profile' => 'inactive',
            'username' => 'api', 'password' => 'secret', 'status' => 'active',
        ]);
        AppIpPool::create([
            'mikrotik_router_id' => $router->id,
            'name' => 'customer-pool',
            'ranges' => '10.99.3.0/24',
            'status' => 'active',
        ]);

        $this->actingAs($user)->post(route('customers.store'), [
            ...$base,
            'connection_id' => 'POOL-CLASH-1',
            'fixed_ip_address' => '10.99.3.254',
            'mikrotik_router_ids' => [$router->id],
        ])->assertSessionHasErrors('fixed_ip_address');
    }

    public function test_special_customer_cannot_be_force_inactivated(): void
    {
        $user = User::factory()->create();
        foreach (['manage_customers', 'force_service_status'] as $name) {
            $user->permissions()->attach(Permission::where('name', $name)->firstOrFail());
        }

        $customer = Customer::create([
            'name' => 'No Suspend Party', 'phone' => '01766666666',
            'connection_id' => 'NO-SUSPEND', 'address' => 'Kushtia',
            'status' => 'active', 'is_customer' => true, 'never_suspend' => true,
        ]);

        $this->actingAs($user)
            ->from(route('customers.show', $customer))
            ->post(route('customers.force-inactive', $customer), ['inactive_note' => 'trying to block'])
            ->assertSessionHasErrors('inactive_note');

        $this->assertSame('active', $customer->fresh()->status);
    }

    private function makeImportedSecretForCustomer(Customer $customer, string $key): MikrotikImportedSecret
    {
        $router = MikrotikRouter::create([
            'name' => 'Import Router '.$key,
            'ip_address' => '10.0.0.'.str_pad((string) ($customer->id + 10), 3, '0', STR_PAD_LEFT),
            'username' => 'admin',
            'password' => 'admin123',
            'status' => 'active',
            'api_port' => 8728,
        ]);

        return MikrotikImportedSecret::create([
            'mikrotik_router_id' => $router->id,
            'customer_id' => $customer->id,
            'routeros_id' => 'routeros-'.$customer->id.'-'.$key,
            'name' => $customer->connection_id,
            'password' => null,
            'service' => 'pppoe',
            'profile' => null,
            'local_address' => null,
            'remote_address' => null,
            'disabled' => false,
            'router_comment' => null,
            'notes' => null,
            'imported_at' => now(),
        ]);
    }
}
