<?php

namespace App\Listeners;

use App\Events\OrderStatusChanged;
use App\Mail\NumberActivationExpired;
use App\Mail\NumberSmsReceived;
use App\Models\Notification;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;

class NotifyOrderStatusChanged
{
    public function handle(OrderStatusChanged $event): void
    {
        if ($event->oldStatus === $event->newStatus) {
            return;
        }

        $order = $event->order;

        if ($order->isNumber()) {
            $this->notifyNumber($order, $event->newStatus);

            return;
        }

        $notify = match ($event->newStatus) {
            Order::STATUS_COMPLETED => true,
            Order::STATUS_FAILED => true,
            Order::STATUS_REFUNDED => true,
            Order::STATUS_CANCELLED => true,
            Order::STATUS_EXPIRED => true,
            default => false,
        };

        if (! $notify) {
            return;
        }

        $type = match ($event->newStatus) {
            Order::STATUS_COMPLETED => 'success',
            Order::STATUS_FAILED => 'danger',
            Order::STATUS_EXPIRED => 'warning',
            default => 'info',
        };

        $label = match ($event->newStatus) {
            Order::STATUS_COMPLETED => 'completed',
            Order::STATUS_FAILED => 'failed',
            Order::STATUS_REFUNDED => 'refunded',
            Order::STATUS_CANCELLED => 'cancelled',
            Order::STATUS_EXPIRED => 'expired',
            default => $event->newStatus,
        };

        Notification::notify(
            $order->user_id,
            'Order '.$label,
            'Your order '.$order->reference.' is now '.$label.'.',
            $type,
            route('orders'),
            ['order_id' => $order->id]
        );
    }

    private function notifyNumber(Order $order, string $newStatus): void
    {
        match ($newStatus) {
            Order::STATUS_COMPLETED => $this->notifyNumberReceived($order),
            Order::STATUS_EXPIRED => $this->notifyNumberExpired($order),
            Order::STATUS_CANCELLED => Notification::notify(
                $order->user_id,
                'Activation cancelled',
                'The SMS activation for '.$order->title.' was cancelled and your payment has been refunded.',
                'info',
                route('numbers'),
                ['order_id' => $order->id]
            ),
            Order::STATUS_FAILED => Notification::notify(
                $order->user_id,
                'Activation failed',
                'The SMS activation for '.$order->title.' failed. Your payment has been refunded.',
                'danger',
                route('numbers'),
                ['order_id' => $order->id]
            ),
            default => null,
        };
    }

    private function notifyNumberReceived(Order $order): void
    {
        if ($order->sms_status !== Order::SMS_RECEIVED) {
            return;
        }

        Notification::notify(
            $order->user_id,
            'SMS code received',
            'Your verification code for '.$order->title.' is '.$order->sms_code.'.',
            'success',
            route('numbers'),
            ['order_id' => $order->id]
        );

        if (filled($order->user->email)) {
            Mail::to($order->user->email)->send(new NumberSmsReceived($order));
        }
    }

    private function notifyNumberExpired(Order $order): void
    {
        Notification::notify(
            $order->user_id,
            'Activation expired',
            'The SMS activation for '.$order->title.' expired before a code arrived. Your payment has been refunded to your wallet.',
            'warning',
            route('numbers'),
            ['order_id' => $order->id]
        );

        if (filled($order->user->email)) {
            Mail::to($order->user->email)->send(new NumberActivationExpired($order));
        }
    }
}
