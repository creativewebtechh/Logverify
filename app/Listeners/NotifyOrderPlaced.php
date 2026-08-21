<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Models\Notification;
use App\Models\User;

class NotifyOrderPlaced
{
    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        if ($order->isNumber()) {
            Notification::notify(
                $order->user_id,
                'Number reserved',
                'Your '.$order->title.' number has been reserved. Waiting for the SMS code — check My Numbers.',
                'info',
                route('numbers'),
                ['order_id' => $order->id]
            );

            return;
        }

        $message = 'Order #'.$order->reference.' has been received and is being processed.';

        if ($order->isBoost()) {
            $message = 'Your order for '.number_format((int) $order->quantity).' '.$order->title.' is being processed. Order #'.$order->reference.'.';
        }

        Notification::notify(
            $order->user_id,
            'Order placed',
            $message,
            'info',
            route('orders'),
            ['order_id' => $order->id]
        );

        foreach (User::where('role', 'admin')->where('id', '!=', $order->user_id)->get() as $admin) {
            Notification::notify(
                $admin->id,
                'New order',
                'New order '.$order->reference.' from user #'.$order->user_id.'.',
                'info',
                route('admin.orders'),
                ['order_id' => $order->id]
            );
        }
    }
}
