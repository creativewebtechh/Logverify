<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    public function __construct(
        private WalletService $wallet,
    ) {}

    public function commissionPercent(): float
    {
        return (float) Setting::get('referral_commission_percent', config('app.referral_commission_percent', 10));
    }

    public function createReferral(User $referrer, User $referredUser): Referral
    {
        return Referral::firstOrCreate([
            'user_id' => $referrer->id,
            'referred_user_id' => $referredUser->id,
        ], [
            'bonus_amount' => 0,
            'status' => Referral::STATUS_PENDING,
        ]);
    }

    /**
     * Called when a referred user makes their first successful deposit.
     * Credits a commission to the referrer based on the deposit amount.
     */
    public function creditCommissionOnFirstDeposit(User $referredUser, float $depositAmount): void
    {
        $referral = Referral::where('referred_user_id', $referredUser->id)->first();
        if ($referral === null) {
            return;
        }

        DB::transaction(function () use ($referral, $depositAmount) {
            $percent = $this->commissionPercent();
            $commission = round($depositAmount * ($percent / 100), 2);

            if ($commission <= 0) {
                return;
            }

            $referral->update([
                'bonus_amount' => $commission,
                'status' => Referral::STATUS_READY,
            ]);

            $referrer = $referral->referrer;
            $this->wallet->credit(
                $referrer,
                $commission,
                'referral_commission',
                "Referral bonus from {$referral->referredUser->name}'s first deposit",
                ['referral_id' => $referral->id]
            );

            $referral->update(['status' => Referral::STATUS_CLAIMED, 'claimed_at' => now()]);
        });
    }

    public function stats(User $user): array
    {
        $cacheKey = "referral-stats-{$user->id}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user) {
            return [
                'total' => Referral::where('user_id', $user->id)->count(),
                'ready' => Referral::where('user_id', $user->id)->where('status', Referral::STATUS_READY)->count(),
                'claimed' => Referral::where('user_id', $user->id)->where('status', Referral::STATUS_CLAIMED)->count(),
                'earned' => (float) Referral::where('user_id', $user->id)->where('status', Referral::STATUS_CLAIMED)->sum('bonus_amount'),
            ];
        });
    }
}
