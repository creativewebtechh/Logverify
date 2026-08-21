<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\ReferralService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Register extends Component
{
    #[Layout('layouts.guest', ['cardClass' => 'max-w-lg'])]
    #[Title('Create account')]
    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $pin = '';

    public string $pin_confirmation = '';

    public string $referral_code = '';

    public function mount()
    {
        $this->referral_code = request()->query('ref', '');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:32', 'regex:/^[a-zA-Z0-9_.]+$/', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'pin' => ['required', 'digits:4'],
            'pin_confirmation' => ['required', 'same:pin'],
            'referral_code' => ['nullable', 'string', 'max:16', 'exists:users,referral_code'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.regex' => 'Username may only contain letters, numbers, underscores and dots.',
            'username.unique' => 'This username is already taken.',
            'referral_code.exists' => 'This referral code is not valid.',
            'pin.digits' => 'Your transaction PIN must be exactly 4 digits.',
            'pin_confirmation.same' => 'The transaction PIN confirmation does not match.',
        ];
    }

    public function register(WalletService $wallets, ReferralService $referrals)
    {
        $data = $this->validate();

        $user = User::create([
            'name' => $data['name'],
            'username' => filled($data['username']) ? $data['username'] : null,
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'transaction_pin' => $data['pin'],
        ]);

        $wallets->getOrCreateWallet($user);

        if ($data['referral_code'] ?? null) {
            $referrer = User::where('referral_code', $data['referral_code'])->first();
            if ($referrer && $referrer->id !== $user->id) {
                $referrals->createReferral($referrer, $user);
            }
        }

        Auth::login($user);
        request()->session()->regenerate();

        $user->sendEmailVerificationNotification();

        session()->flash('registered', true);

        return redirect()->route('verification.notice');
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
