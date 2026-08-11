<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    public function test_login_page_returns_a_successful_response(): void
    {
        $response = $this->get('/login');

        $response
            ->assertStatus(200)
            ->assertSee('<details class="page-help page-help--login" data-page-help>', false)
            ->assertSee('এই পেজের বিস্তারিত নির্দেশিকা')
            ->assertDontSee('<details open', false);
    }

    public function test_authenticated_pages_render_the_minimized_page_guide(): void
    {
        $user = User::factory()->create();
        $user->permissions()->attach(Permission::where('name', 'view_dashboard')->value('id'));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('<details class="page-help page-help--app" data-page-help>', false)
            ->assertSee('ড্যাশবোর্ড — সারসংক্ষেপ দেখা')
            ->assertDontSee('<details open', false);
    }
}
