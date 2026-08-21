<?php

namespace Tests\Feature;

use App\Livewire\Admin\ManageNumbers;
use App\Models\Number;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageNumbersTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin@numbers.test',
            'password' => bcrypt('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);
    }

    public function test_admin_can_add_number_from_panel(): void
    {
        Livewire::actingAs($this->admin())
            ->test(ManageNumbers::class)
            ->set('country', 'Nigeria')
            ->set('category', 'sms')
            ->set('masked_number', '+2348000000001')
            ->set('price', 250)
            ->set('provider_service_id', 'wa')
            ->call('add')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('numbers', [
            'country' => 'Nigeria',
            'category' => 'sms',
            'number' => '+2348000000001',
            'provider_service_id' => 'wa',
            'status' => 'available',
        ]);
    }

    public function test_admin_can_edit_number_from_panel(): void
    {
        $number = Number::create([
            'country' => 'Nigeria',
            'category' => 'sms',
            'number' => '+2348000000001',
            'masked_number' => '+234 (•••) •••-0001',
            'price' => 250,
            'status' => 'available',
        ]);

        Livewire::actingAs($this->admin())
            ->test(ManageNumbers::class)
            ->call('edit', $number->id)
            ->set('country', 'Kenya')
            ->set('price', 300)
            ->set('status', 'sold')
            ->call('add')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('numbers', [
            'id' => $number->id,
            'country' => 'Kenya',
            'price' => '300.00',
            'status' => 'sold',
        ]);
    }

    public function test_admin_can_delete_number_from_panel(): void
    {
        $number = Number::create([
            'country' => 'Nigeria',
            'category' => 'whatsapp',
            'number' => '+2348000000002',
            'masked_number' => '+234 (•••) •••-0002',
            'price' => 500,
            'status' => 'available',
        ]);

        Livewire::actingAs($this->admin())
            ->test(ManageNumbers::class)
            ->call('delete', $number->id);

        $this->assertDatabaseMissing('numbers', ['id' => $number->id]);
    }
}
