<?php

namespace App\Listeners;

use App\Events\DepositSucceeded;
use App\Mail\DepositFunded;
use Illuminate\Support\Facades\Mail;

class SendDepositEmail
{
    public function handle(DepositSucceeded $event): void
    {
        Mail::to($event->transaction->user)
            ->send(new DepositFunded($event->transaction));
    }
}
