<?php

namespace App\Listeners;

use App\Events\DepositSucceeded;
use App\Models\Notification;
use App\Support\Money;

class NotifyDepositSucceeded
{
    public function handle(DepositSucceeded $event): void
    {
        $transaction = $event->transaction;

        Notification::notify(
            $transaction->user_id,
            'Wallet funded',
            'Your deposit of '.Money::format($transaction->amount).' was successful.',
            'success',
            route('wallet.transactions'),
            ['transaction_id' => $transaction->id]
        );
    }
}
