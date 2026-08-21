<?php

namespace App\Livewire;

use App\Services\PinService;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class AccountSecurity extends Component
{
    public string $current_pin = '';

    public string $pin = '';

    public string $pin_confirmation = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function rules(): array
    {
        $rules = [
            'pin' => ['required', 'string', 'digits:4', 'confirmed'],
            'pin_confirmation' => ['required', 'string'],
        ];

        if (auth()->user()->hasPin()) {
            $rules['current_pin'] = ['required', 'string', 'digits:4'];
        }

        return $rules;
    }

    public function passwordRules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function updatePin(): void
    {
        $this->validate();

        $user = auth()->user();
        $service = app(PinService::class);
        $wasSet = $user->hasPin();

        if ($wasSet) {
            if ($service->isLocked($user)) {
                $this->addError('current_pin', 'Too many failed attempts. Your transaction PIN is temporarily locked. Try again in '.$service->lockoutSecondsRemaining($user).' seconds.');

                return;
            }

            if (! $service->verify($user, $this->current_pin)) {
                $remaining = $service->attemptsRemaining($user);
                $this->addError('current_pin', $remaining > 0
                    ? 'That PIN is incorrect. '.$remaining.' attempt'.($remaining === 1 ? '' : 's').' remaining before your PIN is temporarily locked.'
                    : 'Too many failed attempts. Your transaction PIN is temporarily locked. Try again in '.$service->lockoutSecondsRemaining($user).' seconds.');

                return;
            }
        }

        $service->set($user, $this->pin);

        $this->reset(['current_pin', 'pin', 'pin_confirmation']);

        session()->flash('success', $wasSet ? 'Your transaction PIN has been updated.' : 'Your transaction PIN has been set.');
    }

    public function updatePassword(): void
    {
        $this->validate($this->passwordRules());

        $user = auth()->user();

        if (! Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'The password you entered does not match your current password.');

            return;
        }

        $user->forceFill(['password' => $this->password])->save();

        $this->reset(['current_password', 'password', 'password_confirmation']);

        session()->flash('success', 'Your password has been updated.');
    }

    public function render()
    {
        return view('livewire.account-security');
    }
}
