<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthLoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear both the in-controller limiter key and the route-level
        // throttle middleware buckets so each test starts from zero.
        Cache::flush();
        RateLimiter::clear('user@example.com|127.0.0.1');
    }

    public function test_repeated_failed_logins_are_locked_out(): void
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login.store'), [
                'email' => 'user@example.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        // Sixth attempt is refused before credentials are checked, even with the
        // correct password.
        $response = $this->post(route('login.store'), [
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertStringContainsString(
            'Too many login attempts',
            session('errors')->first('email')
        );
    }

    public function test_successful_login_clears_the_throttle_counter(): void
    {
        User::factory()->create(['email' => 'user@example.com']);

        foreach (range(1, 3) as $ignored) {
            $this->post(route('login.store'), [
                'email' => 'user@example.com',
                'password' => 'wrong-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), [
            'email' => 'user@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertSame(0, RateLimiter::attempts('user@example.com|127.0.0.1'));
    }
}
