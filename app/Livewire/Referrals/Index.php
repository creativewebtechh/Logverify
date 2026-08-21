<?php

namespace App\Livewire\Referrals;

use App\Models\Referral;
use App\Models\User;
use App\Services\ReferralService;
use Livewire\Component;

class Index extends Component
{
    public ?string $email = null;

    public function stats(ReferralService $referrals): array
    {
        return $referrals->stats(auth()->user()) + ['commission' => $referrals->commissionPercent()];
    }

    public function invite(ReferralService $referrals)
    {
        $this->validate([
            'email' => ['required', 'email', 'different:'.auth()->user()->email],
        ], [
            'email.required' => 'Enter your friend\'s email address.',
            'email.email' => 'Enter a valid email address.',
            'email.different' => 'You cannot invite yourself.',
        ]);

        $invitee = User::where('email', $this->email)->first();

        if ($invitee === null) {
            $this->addError('email', 'This email is not registered on Logverify yet.');

            return;
        }

        if ($invitee->id === auth()->id()) {
            $this->addError('email', 'You cannot invite yourself.');

            return;
        }

        $referrals->createReferral(auth()->user(), $invitee);

        $this->reset('email');

        session()->flash('success', 'Invitation sent. You\'ll earn '.$referrals->commissionPercent().'% when they make their first deposit.');
    }

    public function render(ReferralService $referrals)
    {
        return view('livewire.referrals.index', [
            'referrals' => Referral::with(['referredUser'])
                ->where('user_id', auth()->id())
                ->latest()
                ->paginate(15),
            'stats' => $this->stats($referrals),
        ]);
    }
}
