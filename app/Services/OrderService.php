<?php

namespace App\Services;

use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Models\Account;
use App\Models\Order;
use App\Models\Provider;
use App\Models\ProviderLog;
use App\Models\Service;
use App\Models\ServiceProvider;
use App\Models\Setting;
use App\Models\Tool;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Providers\Contracts\ProviderConfig;
use App\Services\Providers\ProviderException;
use App\Services\Providers\ProviderRouter;
use App\Services\Providers\ProviderSelector;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private WalletService $wallet,
        private PaymentService $payment,
        private ProviderSelector $selector,
    ) {}

    public function buyTool(User $user, Tool $tool): Order
    {
        $this->assertForSale($tool->status === 'active', 'This tool is currently unavailable.');
        $this->assertForSale((int) ($tool->stock ?? 1) > 0, 'This tool is out of stock.');

        $order = $this->createOrder($user, $tool, $tool->price);

        $this->decrementStock($tool);

        return $order;
    }

    public function buyAccount(User $user, Account $account): Order
    {
        $this->assertForSale($account->status === 'available', 'This account is no longer available.');
        $this->assertForSale((int) ($account->stock ?? 1) > 0, 'This account is out of stock.');

        $order = $this->createOrder($user, $account, $account->price);

        $credentials = $account->credentials ?? $account->meta['credentials'] ?? [];

        if (filled($credentials)) {
            $order->update([
                'status' => Order::STATUS_COMPLETED,
                'completed_at' => now(),
                'meta' => array_merge($order->meta ?? [], ['account' => $credentials]),
            ]);
        }

        if ($order->status === Order::STATUS_FAILED) {
            return $order;
        }

        $this->decrementStock($account);

        return $order;
    }

    /**
     * Place a social boost order atomically.
     *
     * The wallet debit, Order record and provider call all happen inside a single
     * DB transaction. If the provider rejects or cannot be reached, the whole
     * transaction is rolled back — the user is never charged for a failed order —
     * and the attempt is persisted to provider_logs as an audit trail.
     *
     * $min/$max override the service row limits so the UI can enforce the live
     * provider bounds fetched from the catalogue.
     *
     * @throws \DomainException when the service is off-sale, quantity is out of
     *                          range, the wallet has insufficient funds, or the
     *                          provider could not place the order
     */
    public function buyBoost(User $user, Service $service, int $quantity, array $meta = [], ?int $min = null, ?int $max = null): Order
    {
        $min ??= (int) $service->min_qty;
        $max ??= (int) $service->max_qty;

        $this->assertForSale($service->status === 'active', 'This service is currently unavailable.');
        $this->assertForSale(
            $quantity >= $min && $quantity <= $max,
            'Quantity must be between '.number_format($min).' and '.number_format($max).'.'
        );

        $this->assertNoDuplicate($user, $service, $quantity, $meta['target'] ?? null);

        $total = $service->priceFor($quantity);

        $deferredLogs = [];
        $mode = (string) Setting::get('smm.orders.provider_selection', ProviderSelector::MODE_AUTO);
        $selected = app(ProviderRouter::class)->configured(Provider::CHANNEL_BOOST)
            ? $this->selector->select($service, ['mode' => $mode, 'quantity' => $quantity])
            : null;

        try {
            $order = DB::transaction(function () use ($user, $service, $total, $quantity, $meta, $selected, &$deferredLogs) {
                $order = $this->createOrder($user, $service, $total, $quantity, $meta);

                if ($selected) {
                    $order->update([
                        'meta' => array_merge($order->meta ?? [], [
                            'provider_id' => $selected->provider_id,
                            'provider_service_id' => $selected->provider_service_id,
                            'provider_mode' => $selected->provider->name,
                        ]),
                    ]);
                }

                $order->update(['status' => Order::STATUS_PROCESSING]);

                if (app(ProviderRouter::class)->configured(Provider::CHANNEL_BOOST)) {
                    $this->dispatchBoostOrder($order, $selected, $deferredLogs);
                } else {
                    $this->pushTimeline($order, Order::STATUS_PROCESSING, 'Order is being processed.');
                }

                return $order;
            });

            $this->flushLogs($deferredLogs);
            OrderPlaced::dispatch($order);

            return $order;
        } catch (ProviderException $e) {
            // The transaction has been rolled back: the order and wallet debit are
            // gone. Persist the audit trail that the in-transaction writes lost.
            $this->flushLogs($deferredLogs);

            throw new \DomainException(
                'The provider could not complete your order right now. Your wallet was not charged. Please try again in a few minutes.',
                0,
                $e
            );
        }
    }

    /**
     * Call the boost provider and record the result. Runs inside the buyBoost
     * transaction; a failure propagates so the transaction can roll back.
     *
     * @param  ServiceProvider|null  $selected  the pivot chosen by the ProviderSelector
     * @param  array<int, array<string, mixed>>  $deferredLogs
     */
    private function dispatchBoostOrder(Order $order, ?ServiceProvider $selected, array &$deferredLogs): void
    {
        $service = $order->orderable;

        $providerServiceId = $selected
            ? (string) $selected->provider_service_id
            : (string) $service->provider_service_id;

        $used = null;
        $result = app(ProviderRouter::class)->call(
            Provider::CHANNEL_BOOST,
            ProviderRouter::TYPE_ORDER,
            fn ($provider) => $provider->placeOrder($order->reference, [
                'service' => $service->slug,
                'platform' => $service->platform,
                'quantity' => $order->quantity,
                'target' => $order->meta['target'] ?? null,
                'provider_service_id' => $providerServiceId,
            ]),
            $used,
            $selected?->provider_id,
            $deferredLogs
        );

        $order->update([
            'status' => Order::STATUS_PROCESSING,
            'meta' => array_merge($order->meta ?? [], $this->providerMeta($used, $result), [
                'provider_id' => $used?->providerId(),
                'provider_service_id' => $providerServiceId,
            ]),
        ]);

        $this->pushTimeline($order, Order::STATUS_PROCESSING, 'Order submitted to the provider.');
    }

    private function providerMeta(ProviderConfig $provider, array $result): array
    {
        return [
            'provider' => $provider->name(),
            'provider_reference' => $result['reference'],
            'provider_number' => $result['number'] ?? null,
            'account' => $result['account'] ?? null,
            'provider_message' => $result['message'],
            'provider_status_code' => $result['status_code'],
            'provider_raw' => $result['raw'],
        ];
    }

    public function refund(User $user, Order $order, string $reason = ''): Order
    {
        $this->assertOwnerOrAdmin($user, $order);
        $this->assertForSale($order->isRefundable(), 'This order cannot be refunded.');
        $this->assertNotRefunded($order);

        return DB::transaction(function () use ($order, $reason) {
            $this->wallet->credit(
                $order->user,
                (float) $order->total,
                Transaction::TYPE_REFUND,
                $reason !== '' ? $reason : 'Refund for order '.$order->reference,
                ['order_id' => $order->id, 'reference' => $order->reference.'-R'.now()->timestamp]
            );

            $oldStatus = $order->status;
            $order->update([
                'status' => Order::STATUS_REFUNDED,
                'completed_at' => null,
                'meta' => array_merge($order->meta ?? [], [
                    'refunded_at' => now()->toDateTimeString(),
                    'refund_reason' => $reason,
                ]),
            ]);

            $this->pushTimeline($order, Order::STATUS_REFUNDED, $reason !== '' ? $reason : 'Refunded to wallet.');
            OrderStatusChanged::dispatch($order->fresh(), $oldStatus, Order::STATUS_REFUNDED, $reason !== '' ? $reason : 'Refunded to wallet.');

            return $order->fresh();
        });
    }

    public function cancel(User $user, Order $order, string $reason = ''): Order
    {
        $this->assertOwnerOrAdmin($user, $order);

        $this->assertForSale(
            in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PAID, Order::STATUS_PROCESSING], true),
            'Only pending or processing orders can be cancelled.'
        );
        $this->assertNotRefunded($order);

        return DB::transaction(function () use ($order, $reason) {
            $this->wallet->credit(
                $order->user,
                (float) $order->total,
                Transaction::TYPE_REFUND,
                $reason !== '' ? $reason : 'Cancelled order '.$order->reference,
                ['order_id' => $order->id, 'reference' => $order->reference.'-C'.now()->timestamp]
            );

            $oldStatus = $order->status;
            $order->update([
                'status' => Order::STATUS_CANCELLED,
                'completed_at' => null,
                'meta' => array_merge($order->meta ?? [], [
                    'cancelled_at' => now()->toDateTimeString(),
                    'cancel_reason' => $reason,
                ]),
            ]);

            $this->pushTimeline($order, Order::STATUS_CANCELLED, $reason !== '' ? $reason : 'Cancelled by customer.');
            OrderStatusChanged::dispatch($order->fresh(), $oldStatus, Order::STATUS_CANCELLED, $reason !== '' ? $reason : 'Cancelled.');

            return $order->fresh();
        });
    }

    public function retry(User $user, Order $order, string $providerMode = ProviderSelector::MODE_AUTO): Order
    {
        $this->assertOwnerOrAdmin($user, $order);

        if (! $order->isBoost()) {
            throw new \DomainException('Only boost orders can be retried.');
        }

        $this->assertForSale($order->status === Order::STATUS_FAILED, 'Only failed orders can be retried.');

        $service = $order->orderable;

        if (! $service) {
            throw new \DomainException('The service is no longer available.');
        }

        $selected = $this->selector->select($service, ['mode' => $providerMode, 'quantity' => (int) $order->quantity]);
        $deferredLogs = [];

        try {
            $updated = DB::transaction(function () use ($order, $selected, &$deferredLogs) {
                $this->dispatchBoostOrder($order, $selected, $deferredLogs);
                $this->pushTimeline($order->fresh(), Order::STATUS_PROCESSING, 'Order re-submitted to the provider.');

                return $order->fresh();
            });

            $this->flushLogs($deferredLogs);

            return $updated;
        } catch (ProviderException $e) {
            $this->flushLogs($deferredLogs);

            throw new \DomainException('Retry failed: the provider could not place the order. Please try again later.', 0, $e);
        }
    }

    private function assertNoDuplicate(User $user, Service $service, int $quantity, ?string $target): void
    {
        if (! (bool) Setting::get('smm.orders.duplicate_protection', true)) {
            return;
        }

        $window = (int) Setting::get('smm.orders.duplicate_window_minutes', 10);

        $exists = Order::query()
            ->where('user_id', $user->id)
            ->where('orderable_type', Service::class)
            ->where('orderable_id', $service->id)
            ->where('quantity', $quantity)
            ->whereIn('status', [Order::STATUS_PENDING, Order::STATUS_PAID, Order::STATUS_PROCESSING])
            ->where('created_at', '>=', now()->subMinutes($window))
            ->when($target !== null && $target !== '', fn ($q) => $q->where('meta->target', $target))
            ->exists();

        if ($exists) {
            throw new \DomainException('You already placed this order recently and it is still processing. Check My Orders.');
        }
    }

    private function assertNotRefunded(Order $order): void
    {
        if ($order->status === Order::STATUS_REFUNDED || $this->hasRefunded($order)) {
            throw new \DomainException('This order has already been refunded.');
        }
    }

    private function hasRefunded(Order $order): bool
    {
        return Transaction::query()
            ->where('user_id', $order->user_id)
            ->where('type', Transaction::TYPE_REFUND)
            ->where('meta->order_id', $order->id)
            ->exists();
    }

    private function assertOwnerOrAdmin(User $user, Order $order): void
    {
        if ($order->user_id !== $user->id && ! $user->isAdmin()) {
            throw new \DomainException('You cannot perform this action on this order.');
        }
    }

    private function pushTimeline(Order $order, string $status, string $note, array $meta = []): void
    {
        $order->statusHistory()->create([
            'status' => $status,
            'note' => $note,
            'meta' => $meta,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $logs
     */
    private function flushLogs(array $logs): void
    {
        foreach ($logs as $payload) {
            ProviderLog::create($payload);
        }
    }

    private function createOrder(User $user, Tool|Service|Account $item, float $total, int $quantity = 1, array $meta = []): Order
    {
        if (! $this->wallet->hasBalance($user, $total)) {
            throw new \DomainException('Insufficient wallet balance. Please fund your wallet first.');
        }

        $title = $item->name ?? $item->title ?? $item->type ?? class_basename($item);
        $channel = match (true) {
            $item instanceof Service => Order::CHANNEL_BOOST,
            $item instanceof Account => Order::CHANNEL_ACCOUNTS,
            $item instanceof Tool => Order::CHANNEL_TOOLS,
            default => Order::CHANNEL_BOOST,
        };

        return DB::transaction(function () use ($user, $item, $total, $quantity, $meta, $title, $channel) {
            $order = Order::create([
                'user_id' => $user->id,
                'orderable_type' => $item::class,
                'orderable_id' => $item->id,
                'channel' => $channel,
                'title' => $title,
                'quantity' => $quantity,
                'unit_price' => $quantity === 1 ? $total : (float) $item->price_per_unit,
                'total' => $total,
                'status' => Order::STATUS_PAID,
                'reference' => $this->wallet->reference('ORD'),
                'payment_method' => 'wallet',
                'meta' => $meta,
                'paid_at' => now(),
            ]);

            $this->wallet->debit(
                $user,
                $total,
                Transaction::TYPE_PURCHASE,
                'Purchase: '.$title,
                ['order_id' => $order->id, 'reference' => $order->reference]
            );

            $this->pushTimeline($order, Order::STATUS_PAID, 'Order paid from wallet.');

            return $order;
        });
    }

    private function assertForSale(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new \DomainException($message);
        }
    }

    private function decrementStock(Tool|Account $item): void
    {
        $stock = (int) ($item->stock ?? 1);

        if ($stock <= 1) {
            $item->update(['status' => $item instanceof Tool ? 'inactive' : 'sold']);

            return;
        }

        $item->update(['stock' => $stock - 1]);
    }
}
