<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Login extends Component
{
    #[Layout('layouts.guest')]
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    public function login()
    {
        $credentials = $this->validate();

        $key = 'login:'.strtolower($credentials['email']).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $this->addError('email', "Too many login attempts. Please try again in {$seconds} seconds.");

            return;
        }

        if (! Auth::attempt($credentials, $this->remember)) {
            RateLimiter::hit($key, 60);
            $this->addError('email', 'These credentials do not match our records.');

            return;
        }

        RateLimiter::clear($key);

        request()->session()->regenerate();

        if (! Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
