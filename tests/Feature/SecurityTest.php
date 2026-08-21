<?php

namespace Tests\Feature;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_applied_to_responses(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('X-XSS-Protection', '0')
            ->assertHeader('Permissions-Policy');
    }

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        $email = 'lockout@logverify.test';

        for ($i = 0; $i < 5; $i++) {
            Livewire::test(Login::class)
                ->set('email', $email)
                ->set('password', 'wrong-password')
                ->call('login')
                ->assertHasErrors(['email' => 'These credentials do not match our records.']);
        }

        $component = Livewire::test(Login::class)
            ->set('email', $email)
            ->set('password', 'wrong-password')
            ->call('login');

        $component->assertHasErrors('email');
        $this->assertStringContainsString(
            'Too many login attempts',
            $component->instance()->getErrorBag()->first('email')
        );
    }

    public function test_webhook_routes_are_rate_limited(): void
    {
        $this->post('/paystack/webhook', [], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertHeader('X-RateLimit-Limit', '60');

        $response = null;

        for ($i = 0; $i < 60; $i++) {
            $response = $this->post('/paystack/webhook', [], ['Accept' => 'application/json']);
        }

        $this->assertNotNull($response);
        $response->assertStatus(429);
    }

    public function test_forgot_password_does_not_reveal_whether_account_exists(): void
    {
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andReturn(Password::INVALID_USER);

        Livewire::test(ForgotPassword::class)
            ->set('email', 'nobody@logverify.test')
            ->call('sendResetLink')
            ->assertSet('sent', true)
            ->assertHasNoErrors();
    }
}
