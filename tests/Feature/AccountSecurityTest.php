<?php

namespace Tests\Feature;

use App\Livewire\AccountSecurity;
use App\Models\User;
use App\Services\PinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Customer',
            'email' => 'customer@security.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);
    }

    public function test_page_renders_and_prompts_user_to_set_a_pin(): void
    {
        Livewire::actingAs($this->user)
            ->test(AccountSecurity::class)
            ->assertSee('Account security')
            ->assertSee('set a transaction PIN yet');
    }

    public function test_user_can_set_a_pin_for_the_first_time(): void
    {
        Livewire::actingAs($this->user)
            ->test(AccountSecurity::class)
            ->set('pin', '4321')
            ->set('pin_confirmation', '4321')
            ->call('updatePin')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('4321', $this->user->fresh()->transaction_pin));
    }

    public function test_mismatched_confirmation_is_rejected(): void
    {
        Livewire::actingAs($this->user)
            ->test(AccountSecurity::class)
            ->set('pin', '4321')
            ->set('pin_confirmation', '9999')
            ->call('updatePin')
            ->assertHasErrors('pin');

        $this->assertNull($this->user->fresh()->transaction_pin);
    }

    public function test_changing_pin_requires_the_current_pin(): void
    {
        app(PinService::class)->set($this->user, '1234');

        Livewire::actingAs($this->user)
            ->test(AccountSecurity::class)
            ->set('current_pin', '0000')
            ->set('pin', '4321')
            ->set('pin_confirmation', '4321')
            ->call('updatePin')
            ->assertHasErrors('current_pin');

        $this->assertTrue(Hash::check('1234', $this->user->fresh()->transaction_pin));
    }

    public function test_user_can_change_their_pin_with_correct_current_pin(): void
    {
        app(PinService::class)->set($this->user, '1234');

        Livewire::actingAs($this->user)
            ->test(AccountSecurity::class)
            ->set('current_pin', '1234')
            ->set('pin', '4321')
            ->set('pin_confirmation', '4321')
            ->call('updatePin')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('4321', $this->user->fresh()->transaction_pin));
    }

    public function test_user_can_change_their_password_with_correct_current_password(): void
    {
        Livewire::actingAs($this->user)
            ->test(AccountSecurity::class)
            ->set('current_password', 'password')
            ->set('password', 'new-secret-pass')
            ->set('password_confirmation', 'new-secret-pass')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('new-secret-pass', $this->user->fresh()->password));
    }

    public function test_changing_password_requires_the_current_password(): void
    {
        Livewire::actingAs($this->user)
            ->test(AccountSecurity::class)
            ->set('current_password', 'wrong-password')
            ->set('password', 'new-secret-pass')
            ->set('password_confirmation', 'new-secret-pass')
            ->call('updatePassword')
            ->assertHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $this->user->fresh()->password));
    }

    public function test_mismatched_password_confirmation_is_rejected(): void
    {
        Livewire::actingAs($this->user)
            ->test(AccountSecurity::class)
            ->set('current_password', 'password')
            ->set('password', 'new-secret-pass')
            ->set('password_confirmation', 'different-pass')
            ->call('updatePassword')
            ->assertHasErrors('password');

        $this->assertTrue(Hash::check('password', $this->user->fresh()->password));
    }
}
