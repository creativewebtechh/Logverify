<?php

namespace App\Livewire\Admin;

use App\Models\Notification;
use App\Models\Order;
use App\Models\Transaction;
use App\Services\Numbers\NumberPurchaseService;
use App\Services\OrderService;
use App\Services\WalletService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ManageOrders extends Component
{
    use WithPagination;

    public ?string $status = null;

    public ?string $channel = null;

    public string $search = '';

    public ?string $from = null;

    public ?string $to = null;

    public ?int $viewingId = null;

    protected $paginationTheme = 'tailwind';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedChannel(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function view(int $orderId): void
    {
        $this->viewingId = $orderId;
    }

    public function closeView(): void
    {
        $this->viewingId = null;
    }

    public function setStatus(int $orderId, string $status, WalletService $wallets): void
    {
        $order = Order::with('user')->findOrFail($orderId);

        if (! in_array($status, [Order::STATUS_PROCESSING, Order::STATUS_COMPLETED, Order::STATUS_FAILED, Order::STATUS_REFUNDED], true)) {
            return;
        }

        $order->update([
            'status' => $status,
            'completed_at' => $status === Order::STATUS_COMPLETED ? now() : $order->completed_at,
        ]);

        if ($status === Order::STATUS_REFUNDED) {
            $wallets->credit(
                $order->user,
                (float) $order->total,
                Transaction::TYPE_REFUND,
                "Refund for order {$order->reference}",
                ['order_id' => $order->id]
            );
        }

        Notification::notify(
            $order->user_id,
            'Order update',
            'Your order '.$order->reference.' is now '.$status.'.',
            $status === Order::STATUS_COMPLETED ? 'success' : 'info',
            route('orders')
        );
    }

    public function refundOrder(int $orderId): void
    {
        try {
            app(OrderService::class)->refund(auth()->user(), Order::findOrFail($orderId), 'Refunded by admin');

            session()->flash('success', 'Order refunded to the customer\'s wallet.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function cancelOrder(int $orderId): void
    {
        try {
            app(OrderService::class)->cancel(auth()->user(), Order::findOrFail($orderId), 'Cancelled by admin');

            session()->flash('success', 'Order cancelled and refunded to the customer\'s wallet.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function retryOrder(int $orderId): void
    {
        try {
            app(OrderService::class)->retry(auth()->user(), Order::findOrFail($orderId), 'auto');

            session()->flash('success', 'Order re-submitted to the provider.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function pollNumber(int $orderId): void
    {
        $order = Order::findOrFail($orderId);

        if (! $order->isNumber()) {
            session()->flash('error', 'This is not a number activation.');

            return;
        }

        try {
            $order = app(NumberPurchaseService::class)->poll($order);

            session()->flash('success', 'Polled provider. SMS status is now '.($order->sms_status ?? 'unknown').'.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function cancelNumber(int $orderId): void
    {
        try {
            app(NumberPurchaseService::class)->cancel(auth()->user(), Order::findOrFail($orderId), 'Cancelled by admin');

            session()->flash('success', 'Number activation cancelled and refunded to the customer\'s wallet.');
        } catch (\Throwable $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function completeNumber(int $orderId, ?string $code = null): void
    {
        $order = Order::findOrFail($orderId);

        if (! $order->isNumber()) {
            session()->flash('error', 'This is not a number activation.');

            return;
        }

        $code = trim((string) $code);

        if ($code === '') {
            session()->flash('error', 'Enter the SMS code first.');

            return;
        }

        $order->update([
            'status' => Order::STATUS_COMPLETED,
            'completed_at' => now(),
            'sms_status' => Order::SMS_RECEIVED,
            'sms_code' => $code,
            'sms_code_at' => now(),
            'expires_at' => null,
        ]);

        Notification::notify(
            $order->user_id,
            'SMS code received',
            'Your SMS verification code is '.$code.'.',
            'success',
            route('orders')
        );

        session()->flash('success', 'Number activation marked as completed with the SMS code.');
    }

    public function render()
    {
        return view('livewire.admin.manage-orders', [
            'orders' => Order::with('user')
                ->when($this->status, fn ($q) => $q->where('status', $this->status))
                ->when($this->channel, fn ($q) => $q->where('channel', $this->channel))
                ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                    ->where('reference', 'like', "%{$this->search}%")
                    ->orWhereHas('user', fn ($u) => $u
                        ->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%"))))
                ->when($this->from, fn ($q) => $q->whereDate('created_at', '>=', $this->from))
                ->when($this->to, fn ($q) => $q->whereDate('created_at', '<=', $this->to))
                ->latest()
                ->paginate(15),
            'viewingOrder' => $this->viewingId !== null
                ? Order::with(['user', 'statusHistory'])->find($this->viewingId)
                : null,
        ]);
    }
}
