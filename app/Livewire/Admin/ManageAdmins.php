<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\WalletService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class ManageAdmins extends Component
{
    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:32', 'regex:/^[a-zA-Z0-9_.]+$/', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Username may only contain letters, numbers, underscores and dots.',
            'username.unique' => 'This username is already taken.',
        ];
    }

    public function createAdmin(WalletService $wallets): void
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'username' => filled($this->username) ? $this->username : null,
            'email' => strtolower($this->email),
            'password' => $this->password,
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        $wallets->getOrCreateWallet($user);

        $this->reset(['name', 'username', 'email', 'password', 'password_confirmation']);

        session()->flash('success', 'Admin account created for '.$user->email.'.');
    }

    public function removeAdmin(int $userId): void
    {
        if ($userId === auth()->id()) {
            session()->flash('error', 'You cannot remove your own admin access.');

            return;
        }

        $user = User::findOrFail($userId);

        if ($user->role !== 'admin') {
            session()->flash('error', 'This user is not an admin.');

            return;
        }

        $user->update(['role' => 'customer']);

        session()->flash('success', 'Admin access removed for '.$user->email.'.');
    }

    public function render()
    {
        return view('livewire.admin.manage-admins', [
            'admins' => User::where('role', 'admin')->latest()->get(),
        ]);
    }
}
