<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class ForgotPassword extends Component
{
    #[Layout('layouts.guest')]
    #[Title('Forgot password')]
    public string $email = '';

    public bool $sent = false;

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    public function sendResetLink()
    {
        $this->validate();

        Password::sendResetLink(['email' => $this->email]);

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
