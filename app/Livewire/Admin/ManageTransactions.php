<?php

namespace App\Livewire\Admin;

use App\Models\Transaction;
use App\Payments\Exceptions\PaymentException;
use App\Services\PaymentService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class ManageTransactions extends Component
{
    use WithPagination;

    public ?string $status = null;

    public ?string $paymentStatus = null;

    public ?string $type = null;

    public ?string $gateway = null;

    public ?string $search = null;

    public ?string $from = null;

    public ?string $to = null;

    protected $paginationTheme = 'tailwind';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPaymentStatus(): void
    {
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedGateway(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedTo(): void
    {
        $this->resetPage();
    }

    /**
     * Ask the gateway to re-verify a stuck pending deposit.
     */
    public function retryVerification(int $transactionId, PaymentService $payments, TransactionService $transactions): void
    {
        $transaction = Transaction::findOrFail($transactionId);

        if ($transaction->status !== Transaction::STATUS_PENDING) {
            session()->flash('status', 'Only pending transactions can be re-verified.');

            return;
        }

        try {
            $notification = $payments->verify($transaction->reference, $transaction->gateway);
            $result = $transactions->fulfil($transaction->gateway, $notification);

            session()->flash(
                'status',
                match ($result) {
                    'success', 'already_processed' => 'Payment verified and wallet credited.',
                    'failed' => 'Gateway reports the payment failed.',
                    'amount_mismatch' => 'Gateway amount does not match the deposit.',
                    'currency_mismatch' => 'Gateway currency does not match the deposit.',
                    'pending' => 'Gateway still reports the payment as pending.',
                    default => 'Payment could not be verified.',
                }
            );
        } catch (PaymentException $e) {
            Log::channel('payments')->warning('Admin retry verification failed.', [
                'transaction_id' => $transaction->id,
                'reference' => $transaction->reference,
                'error' => $e->getMessage(),
            ]);

            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        $deposits = Transaction::query()->where('type', Transaction::TYPE_DEPOSIT);

        return view('livewire.admin.manage-transactions', [
            'transactions' => Transaction::with('user')
                ->when($this->status, fn ($q) => $q->where('status', $this->status))
                ->when($this->paymentStatus, fn ($q) => $q->where('payment_status', $this->paymentStatus))
                ->when($this->type, fn ($q) => $q->where('type', $this->type))
                ->when($this->gateway, fn ($q) => $q->where('gateway', $this->gateway))
                ->when(filled($this->search), fn ($q) => $q->where(function ($q) {
                    $q->where('reference', 'like', "%{$this->search}%")
                        ->orWhere('gateway_reference', 'like', "%{$this->search}%")
                        ->orWhereHas('user', fn ($u) => $u
                            ->where('name', 'like', "%{$this->search}%")
                            ->orWhere('email', 'like', "%{$this->search}%"));
                }))
                ->when($this->from, fn ($q) => $q->whereDate('created_at', '>=', $this->from))
                ->when($this->to, fn ($q) => $q->whereDate('created_at', '<=', $this->to))
                ->latest()
                ->paginate(15),
            'stats' => [
                'funded' => (clone $deposits)->where('status', Transaction::STATUS_SUCCESS)->sum('amount'),
                'pending' => (clone $deposits)->where('status', Transaction::STATUS_PENDING)->count(),
                'failed' => (clone $deposits)->where('status', Transaction::STATUS_FAILED)->count(),
                'total' => (clone $deposits)->count(),
            ],
        ]);
    }
}
