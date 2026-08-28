<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use App\Support\BillingWindow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BillingWindowTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_window_is_noon_to_five_pm(): void
    {
        $this->assertSame(['start' => 12, 'end' => 17], BillingWindow::window());

        $this->assertTrue(BillingWindow::isOpenNow(Carbon::parse('2026-08-28 12:00')));
        $this->assertTrue(BillingWindow::isOpenNow(Carbon::parse('2026-08-28 15:30')));
        $this->assertTrue(BillingWindow::isOpenNow(Carbon::parse('2026-08-28 17:00')));
        $this->assertFalse(BillingWindow::isOpenNow(Carbon::parse('2026-08-28 11:59')));
        $this->assertFalse(BillingWindow::isOpenNow(Carbon::parse('2026-08-28 18:00')));
        $this->assertFalse(BillingWindow::isOpenNow(Carbon::parse('2026-08-28 03:00')));
    }

    public function test_window_follows_the_saved_setting(): void
    {
        AppSetting::setValue(BillingWindow::START_KEY, '9');
        AppSetting::setValue(BillingWindow::END_KEY, '14');

        $this->assertSame(['start' => 9, 'end' => 14], BillingWindow::window());
        $this->assertTrue(BillingWindow::isOpenNow(Carbon::parse('2026-08-28 09:00')));
        $this->assertTrue(BillingWindow::isOpenNow(Carbon::parse('2026-08-28 14:00')));
        $this->assertFalse(BillingWindow::isOpenNow(Carbon::parse('2026-08-28 15:00')));
    }

    public function test_end_before_start_collapses_to_the_start_hour(): void
    {
        AppSetting::setValue(BillingWindow::START_KEY, '16');
        AppSetting::setValue(BillingWindow::END_KEY, '8');

        $this->assertSame(['start' => 16, 'end' => 16], BillingWindow::window());
    }

    public function test_bad_values_fall_back_to_a_sane_window(): void
    {
        AppSetting::setValue(BillingWindow::START_KEY, '-5');
        AppSetting::setValue(BillingWindow::END_KEY, '');

        $this->assertSame(['start' => 0, 'end' => 17], BillingWindow::window());
    }

    public function test_the_organization_form_shows_and_saves_the_window(): void
    {
        $user = $this->invoiceManager();
        $org = Organization::defaultOrganization();

        $this->actingAs($user)->get(route('organizations.edit', $org))
            ->assertOk()
            ->assertSee('Overdue Auto-Disable Schedule');

        $this->actingAs($user)->put(route('organizations.update', $org), [
            'name' => $org->name ?: 'Main',
            'is_active' => 1,
            'billing_disable_start_hour' => 10,
            'billing_disable_end_hour' => 16,
        ])->assertRedirect(route('organizations.index'));

        $this->assertSame(['start' => 10, 'end' => 16], BillingWindow::window());
    }

    public function test_the_form_rejects_an_end_hour_before_the_start(): void
    {
        $user = $this->invoiceManager();
        $org = Organization::defaultOrganization();
        $originalName = $org->name;

        $this->actingAs($user)
            ->from(route('organizations.edit', $org))
            ->put(route('organizations.update', $org), [
                'name' => $org->name ?: 'Main',
                'is_active' => 1,
                'billing_disable_start_hour' => 16,
                'billing_disable_end_hour' => 9,
            ])
            ->assertSessionHasErrors('billing_disable_end_hour');

        $this->assertSame(['start' => 12, 'end' => 17], BillingWindow::window());
        $this->assertSame($originalName, $org->refresh()->name);
    }

    public function test_the_command_skips_outside_the_window_unless_forced(): void
    {
        Carbon::setTestNow('2026-08-28 03:00:00');

        try {
            $this->assertSame(0, Artisan::call('billing:disable-overdue-customers'));
            $this->assertStringContainsString('Skipped', Artisan::output());

            $this->assertSame(0, Artisan::call('billing:disable-overdue-customers', ['--force' => true]));
            $this->assertStringContainsString('Disabled customers: 0', Artisan::output());
        } finally {
            Carbon::setTestNow();
        }
    }

    private function invoiceManager(): User
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'manage_invoices')->firstOrFail());

        return $user->fresh();
    }
}
