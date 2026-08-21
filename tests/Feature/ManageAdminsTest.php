<?php

namespace Tests\Feature;

use App\Livewire\Admin\ManageAdmins;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ManageAdminsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@admins.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_create_an_admin_account(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ManageAdmins::class)
            ->set('name', 'Jane Doe')
            ->set('username', 'janedoe')
            ->set('email', 'jane@admins.test')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->call('createAdmin')
            ->assertHasNoErrors();

        $user = User::where('email', 'jane@admins.test')->first();

        $this->assertNotNull($user);
        $this->assertSame('admin', $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('secret-password', $user->password));
        $this->assertNotNull($user->wallet);
    }

    public function test_admin_cannot_create_an_admin_with_a_duplicate_email(): void
    {
        User::create([
            'name' => 'Existing',
            'email' => 'jane@admins.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        Livewire::actingAs($this->admin())
            ->test(ManageAdmins::class)
            ->set('name', 'Jane Doe')
            ->set('email', 'jane@admins.test')
            ->set('password', 'secret-password')
            ->set('password_confirmation', 'secret-password')
            ->call('createAdmin')
            ->assertHasErrors('email');

        $this->assertSame(2, User::count());
    }

    public function test_admin_cannot_create_an_admin_with_a_weak_password(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ManageAdmins::class)
            ->set('name', 'Jane Doe')
            ->set('email', 'jane@admins.test')
            ->set('password', 'short')
            ->set('password_confirmation', 'short')
            ->call('createAdmin')
            ->assertHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'jane@admins.test']);
    }

    public function test_admin_can_remove_admin_access_from_another_admin(): void
    {
        $other = User::create([
            'name' => 'Other',
            'email' => 'other@admins.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        Livewire::actingAs($this->admin())
            ->test(ManageAdmins::class)
            ->call('removeAdmin', $other->id);

        $this->assertSame('customer', $other->fresh()->role);
    }

    public function test_admin_cannot_remove_their_own_admin_access(): void
    {
        $admin = $this->admin();

        Livewire::actingAs($admin)
            ->test(ManageAdmins::class)
            ->call('removeAdmin', $admin->id);

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_non_admin_cannot_access_the_admins_page(): void
    {
        $user = User::create([
            'name' => 'Customer',
            'email' => 'customer@admins.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('admin.admins'))
            ->assertForbidden();
    }
}
