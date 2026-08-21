<?php

namespace App\Jobs;

use App\Services\Numbers\NumberPurchaseService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PollNumberOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function handle(NumberPurchaseService $purchase): void
    {
        $purchase->pollDueOrders();
    }
}
