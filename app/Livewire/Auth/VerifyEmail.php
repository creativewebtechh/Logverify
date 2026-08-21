<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Verify your email')]
class VerifyEmail extends Component
{
    public function resend(): void
    {
        $user = Auth::user();

        $key = 'verification:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 2)) {
            $seconds = RateLimiter::availableIn($key);
            session()->flash('error', "Please wait {$seconds} seconds before requesting another link.");

            return;
        }

        RateLimiter::hit($key, 60);

        $user->sendEmailVerificationNotification();

        session()->flash('status', 'A fresh verification link has been sent to your email address.');
    }

    public function logout(): void
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        $this->redirectRoute('login');
    }

    public function render()
    {
        return view('livewire.auth.verify-email');
    }
}
