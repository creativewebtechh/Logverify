<?php

namespace App\Services\Numbers;

use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Models\NumberService;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Provider;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Providers\Contracts\NumberProvider;
use App\Services\Providers\Contracts\ProviderConfig;
use App\Services\Providers\ProviderException;
use App\Services\Providers\ProviderFactory;
use App\Services\Providers\ProviderRouter;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Throwable;

class NumberPurchaseService
{
    public function __construct(
        protected WalletService $wallet,
        protected ProviderRouter $router,
        protected NumberPricingService $pricing,
    ) {}

    /**
     * Purchase a virtual number for a service atomically.
     *
     * The wallet debit and order record are committed before the provider is
     * called. If the provider rejects the order, a row-locked, idempotent refund
     * is issued so the customer is never charged for a failed activation. Every
     * order gets an expires_at so the poller can settle or refund it.
     *
     * @throws \DomainException when the service is off-sale, a recent duplicate
     *                          order exists, or the wallet has insufficient funds
     */
    public function purchase(User $user, NumberService $service): Order
    {
        if ($service->status !== NumberService::STATUS_ACTIVE || $service->hidden) {
            throw new \DomainException('This service is currently unavailable.');
        }

        $this->assertNoRecentOrder($user, $service);

        $price = $this->pricing->priceFor($service);

        if (! $this->wallet->hasBalance($user, $price)) {
            throw new \DomainException('Insufficient wallet balance. Please fund your wallet first.');
        }

        $order = $this->createOrder($user, $service, $price);

        if (! $this->router->configured(Provider::CHANNEL_NUMBERS)) {
            return $this->reserve($order, $service, null, null, true);
        }

        try {
            $used = null;
            $deferredLogs = null;
            $result = $this->router->call(
                Provider::CHANNEL_NUMBERS,
                ProviderRouter::TYPE_ORDER,
                fn (NumberProvider $provider) => $provider->purchase($order->reference, [
                    'country' => $service->provider_country_id ?? $service->country_code,
                    'service' => $service->name,
                    'provider_service_id' => $service->provider_service_id ?? '',
                    'price' => $price,
                ]),
                $used,
                $service->provider_id,
                $deferredLogs,
                $order->id
            );
        } catch (ProviderException $e) {
            return $this->failAndRefund($order, $e->getMessage());
        } catch (Throwable $e) {
            return $this->failAndRefund($order, $e->getMessage());
        }

        return $this->reserve($order, $service, $result, $used, false);
    }

    /**
     * Poll a single waiting number order. Idempotent — safe to call repeatedly.
     */
    public function poll(Order $order): Order
    {
        if (! $order->isNumber() || $order->refunded_at !== null) {
            return $order;
        }

        if (! in_array($order->status, [Order::STATUS_PAID, Order::STATUS_PROCESSING], true)) {
            return $order;
        }

        if (in_array($order->sms_status, [Order::SMS_RECEIVED, Order::SMS_CANCELLED, Order::SMS_EXPIRED], true)) {
            return $order;
        }

        if ($order->expires_at !== null && $order->expires_at->isPast()) {
            return $this->expire($order, 'The SMS activation timed out.');
        }

        if (! filled($order->provider_reference)) {
            return $order;
        }

        $driver = $this->driverFor($order);

        if ($driver === null) {
            return $order;
        }

        try {
            $status = $driver->getStatus($order->provider_reference);
        } catch (Throwable) {
            return $order;
        }

        if (! ($status['success'] ?? false)) {
            return $order;
        }

        return match ($status['status'] ?? 'waiting') {
            'received' => $this->markReceived($order, $driver),
            'cancelled' => $this->expire($order, 'The SMS activation was cancelled.'),
            'no_sms', 'expired' => $this->expire($order, 'No SMS was received for this activation.'),
            default => $order,
        };
    }

    public function pollDueOrders(int $limit = 100): int
    {
        $orders = Order::numbers()
            ->where('status', Order::STATUS_PROCESSING)
            ->where('sms_status', Order::SMS_WAITING)
            ->whereNotNull('provider_reference')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderBy('expires_at')
            ->limit($limit)
            ->get();

        $count = 0;

        foreach ($orders as $order) {
            $this->poll($order);
            $count++;
        }

        return $count;
    }

    public function releaseExpired(int $limit = 200): int
    {
        $orders = Order::numbers()
            ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_PROCESSING])
            ->where(function ($q) {
                $q->where('expires_at', '<', now())
                    ->orWhere(fn ($q2) => $q2->whereNull('expires_at')->where('created_at', '<', now()->subMinutes(10)));
            })
            ->limit($limit)
            ->get();

        $count = 0;

        foreach ($orders as $order) {
            $this->expire($order, 'The SMS activation timed out.');
            $count++;
        }

        return $count;
    }

    /**
     * Cancel a waiting activation and refund the customer.
     */
    public function cancel(User $user, Order $order, string $reason = ''): Order
    {
        $this->assertOwnerOrAdmin($user, $order);

        if (! $order->isNumber()) {
            throw new \DomainException('Only SMS verification orders can be cancelled from here.');
        }

        if (! in_array($order->status, [Order::STATUS_PAID, Order::STATUS_PROCESSING], true)) {
            throw new \DomainException('Only waiting orders can be cancelled.');
        }

        if (! in_array($order->sms_status, [Order::SMS_WAITING, null], true)) {
            throw new \DomainException('This order can no longer be cancelled.');
        }

        if ($order->refunded_at !== null) {
            throw new \DomainException('This order has already been refunded.');
        }

        $this->bestEffortProviderCancel($order);

        $this->refundOrder(
            $order,
            $reason !== '' ? $reason : 'Cancelled before SMS delivery.',
            Order::STATUS_CANCELLED,
            $reason !== '' ? $reason : 'Cancelled by customer.',
            Order::SMS_CANCELLED
        );

        return $order->fresh();
    }

    public function reservationSeconds(NumberService $service): int
    {
        $eta = (int) ($service->eta_seconds ?? Setting::get('smm.numbers.timeout_minutes', 15) * 60);

        return min(14400, max(60, $eta));
    }

    private function createOrder(User $user, NumberService $service, float $price): Order
    {
        return DB::transaction(function () use ($user, $service, $price) {
            $order = Order::create([
                'user_id' => $user->id,
                'orderable_type' => NumberService::class,
                'orderable_id' => $service->id,
                'title' => $service->name,
                'quantity' => 1,
                'unit_price' => $price,
                'total' => $price,
                'status' => Order::STATUS_PAID,
                'channel' => Order::CHANNEL_NUMBERS,
                'reference' => $this->wallet->reference('ORD'),
                'payment_method' => 'wallet',
                'meta' => [
                    'country' => $service->country_name,
                    'country_code' => $service->country_code,
                    'service_name' => $service->name,
                    'provider_service_id' => $service->provider_service_id,
                ],
                'paid_at' => now(),
            ]);

            $this->wallet->debit(
                $user,
                $price,
                Transaction::TYPE_PURCHASE,
                'Purchase: '.$service->name.' ('.$service->country_name.')',
                ['order_id' => $order->id, 'reference' => $order->reference]
            );

            $this->pushTimeline($order, Order::STATUS_PAID, 'Order paid from wallet.');

            return $order;
        }, 5);
    }

    private function reserve(Order $order, NumberService $service, ?array $result, ?ProviderConfig $used, bool $simulated): Order
    {
        $reference = $result['reference'] ?? null;
        $number = $result['number'] ?? null;

        $order->update([
            'status' => Order::STATUS_PROCESSING,
            'provider_id' => $used?->providerId(),
            'provider_reference' => $reference,
            'phone_number' => $number,
            'sms_status' => Order::SMS_WAITING,
            'expires_at' => now()->addSeconds($this->reservationSeconds($service)),
            'meta' => array_merge($order->meta ?? [], array_filter([
                'simulated' => $simulated,
                'provider' => $used?->name(),
                'provider_reference' => $reference,
                'provider_number' => $number,
                'provider_message' => $result['message'] ?? null,
                'provider_raw' => $result['raw'] ?? null,
            ], fn ($value) => $value !== null)),
        ]);

        $note = $number !== null
            ? 'Number reserved. Waiting for the SMS code.'
            : 'Order submitted to the provider. Waiting for the number.';

        $this->pushTimeline($order, Order::STATUS_PROCESSING, $note);
        $this->auditProviderCall($used, true);

        OrderPlaced::dispatch($order->fresh());
        OrderStatusChanged::dispatch($order->fresh(), Order::STATUS_PAID, Order::STATUS_PROCESSING, $note);

        return $order->fresh();
    }

    private function markReceived(Order $order, NumberProvider $driver): Order
    {
        try {
            $sms = $driver->getSms($order->provider_reference);
        } catch (Throwable $e) {
            $sms = ['success' => false, 'message' => $e->getMessage()];
        }

        $code = ($sms['success'] ?? false) ? ($sms['code'] ?? null) : null;

        if ($code === null || $code === '') {
            return $order;
        }

        $oldStatus = $order->status;

        $order->update([
            'status' => Order::STATUS_COMPLETED,
            'completed_at' => now(),
            'sms_status' => Order::SMS_RECEIVED,
            'sms_code' => $code,
            'sms_code_at' => now(),
            'expires_at' => null,
        ]);

        $this->pushTimeline($order, Order::STATUS_COMPLETED, 'SMS code received.');
        OrderStatusChanged::dispatch($order->fresh(), $oldStatus, Order::STATUS_COMPLETED, 'SMS verification code received.');

        return $order->fresh();
    }

    public function expire(Order $order, string $reason): Order
    {
        $this->bestEffortProviderCancel($order);
        $this->refundOrder($order, $reason, Order::STATUS_EXPIRED, $reason, Order::SMS_EXPIRED);

        return $order->fresh();
    }

    private function failAndRefund(Order $order, string $reason): Order
    {
        $this->refundOrder($order, 'Order failed: '.$reason, Order::STATUS_FAILED, $reason);

        return $order->fresh();
    }

    /**
     * Row-locked, idempotent refund. The order row is locked inside a DB
     * transaction so concurrent expiry/cancel/fail paths cannot double-credit.
     */
    private function refundOrder(Order $order, string $note, string $finalStatus, ?string $reason = null, ?string $smsStatus = null): void
    {
        DB::transaction(function () use ($order, $note, $finalStatus, $reason, $smsStatus) {
            $lock = Order::query()->whereKey($order->id)->lockForUpdate()->first();

            if ($lock === null || $lock->refunded_at !== null) {
                return;
            }

            $oldStatus = $lock->status;

            $this->wallet->credit(
                $lock->user,
                (float) $lock->total,
                Transaction::TYPE_REFUND,
                $reason !== null ? 'Refund — '.$reason : $note,
                ['order_id' => $lock->id, 'reference' => $lock->reference.'-R'.$lock->id]
            );

            $lock->update([
                'status' => $finalStatus,
                'refunded_at' => now(),
                'completed_at' => null,
                'sms_status' => $smsStatus ?? $lock->sms_status,
                'meta' => array_merge($lock->meta ?? [], array_filter([
                    'refunded_at' => now()->toDateTimeString(),
                    'refund_reason' => $reason,
                ], fn ($value) => $value !== null)),
            ]);

            $this->pushTimeline($lock, $finalStatus, $note);
            OrderStatusChanged::dispatch($lock->fresh(), $oldStatus, $finalStatus, $note);
        }, 5);
    }

    private function bestEffortProviderCancel(Order $order): void
    {
        if (! filled($order->provider_reference)) {
            return;
        }

        $driver = $this->driverFor($order);

        if ($driver === null) {
            return;
        }

        try {
            $driver->cancel($order->provider_reference);
        } catch (Throwable) {
            // Best effort only — the refund proceeds regardless.
        }
    }

    private function driverFor(Order $order): ?NumberProvider
    {
        if ($order->provider_id !== null) {
            $provider = Provider::query()->find($order->provider_id);

            if ($provider !== null) {
                return ProviderFactory::number($provider);
            }
        }

        if ($this->router->configured(Provider::CHANNEL_NUMBERS)) {
            return $this->router->number();
        }

        return null;
    }

    private function auditProviderCall(?ProviderConfig $used, bool $success): void
    {
        if ($used instanceof Provider) {
            $used->recordCall($success);
        }
    }

    private function assertNoRecentOrder(User $user, NumberService $service): void
    {
        $window = (int) Setting::get('smm.numbers.duplicate_window_seconds', 10);

        $exists = Order::query()
            ->where('user_id', $user->id)
            ->where('orderable_type', NumberService::class)
            ->where('orderable_id', $service->id)
            ->whereIn('status', [Order::STATUS_PAID, Order::STATUS_PROCESSING])
            ->where('created_at', '>=', now()->subSeconds($window))
            ->exists();

        if ($exists) {
            throw new \DomainException('You just purchased this service. Check My Numbers for your active activation.');
        }
    }

    private function assertOwnerOrAdmin(User $user, Order $order): void
    {
        if ($order->user_id !== $user->id && ! $user->isAdmin()) {
            throw new \DomainException('You cannot perform this action on this order.');
        }
    }

    private function pushTimeline(Order $order, string $status, string $note, array $meta = []): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $status,
            'note' => $note,
            'meta' => $meta,
            'user_id' => $order->user_id,
        ]);
    }
}
