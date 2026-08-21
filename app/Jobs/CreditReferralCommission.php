<?php

namespace App\Jobs;

use App\Models\Referral;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreditReferralCommission implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $referredUserId,
        public float $depositAmount,
    ) {}

    public function handle(ReferralService $referrals): void
    {
        $referral = Referral::where('referred_user_id', $this->referredUserId)->first();

        if ($referral === null) {
            return;
        }

        $alreadyClaimed = Transaction::where('user_id', $referral->user_id)
            ->where('type', Transaction::TYPE_REFERRAL_COMMISSION)
            ->where('meta->referral_id', $referral->id)
            ->exists();

        if ($alreadyClaimed) {
            return;
        }

        $referrals->creditCommissionOnFirstDeposit(
            User::find($this->referredUserId),
            $this->depositAmount
        );
    }
}
