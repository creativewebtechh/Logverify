<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class ResetPassword extends Component
{
    #[Layout('layouts.guest')]
    #[Title('Reset password')]
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token)
    {
        $this->token = $token;
        $this->email = request()->query('email', '');
    }

    public function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function resetPassword()
    {
        $data = $this->validate();

        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill(['password' => $password])->save();
            $user->setRememberToken(Str::random(60));
            $user->save();
        });

        if ($status === Password::PASSWORD_RESET) {
            session()->flash('status', __($status));

            return redirect()->route('login');
        }

        $this->addError('email', __($status));
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
