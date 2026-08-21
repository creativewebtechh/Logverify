<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WalletService
{
    public function getOrCreateWallet(User $user): Wallet
    {
        return Wallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'pending_balance' => 0,
                'total_credited' => 0,
                'total_debited' => 0,
                'currency' => config('app.currency', 'NGN'),
            ]
        );
    }

    /**
     * Locked wallet row for in-transaction balance mutations. The row is
     * re-locked after a potential concurrent creation to close the create race.
     */
    private function lockWallet(User $user): Wallet
    {
        $wallet = Wallet::query()->where('user_id', $user->id)->lockForUpdate()->first();

        if ($wallet !== null) {
            return $wallet;
        }

        try {
            $wallet = Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'pending_balance' => 0,
                'total_credited' => 0,
                'total_debited' => 0,
                'currency' => config('app.currency', 'NGN'),
            ]);
        } catch (QueryException) {
            $wallet = Wallet::query()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
        }

        return $wallet;
    }

    public function credit(User $user, float $amount, string $type, ?string $description = null, array $meta = []): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $type, $description, $meta) {
            $wallet = $this->lockWallet($user);
            $wallet->increment('balance', $amount);
            $wallet->increment('total_credited', $amount);

            return $this->record($user, $type, $amount, 'success', $description, $meta, (float) $wallet->balance);
        }, 5);
    }

    /**
     * Credit a wallet without writing a transaction row (used to settle an
     * existing funding transaction idempotently). Returns the wallet and
     * whether this was the user's first ever credit.
     *
     * @return array{0: Wallet, 1: bool}
     */
    public function creditSilently(User $user, float $amount): array
    {
        return DB::transaction(function () use ($user, $amount) {
            $wallet = $this->lockWallet($user);
            $wasFirstDeposit = (float) $wallet->total_credited == 0;

            $wallet->increment('balance', $amount);
            $wallet->increment('total_credited', $amount);

            return [$wallet, $wasFirstDeposit];
        }, 5);
    }

    public function debit(User $user, float $amount, string $type, ?string $description = null, array $meta = []): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $type, $description, $meta) {
            $wallet = $this->lockWallet($user);

            if ((float) $wallet->balance < $amount) {
                throw new \DomainException('Insufficient wallet balance.');
            }

            $wallet->decrement('balance', $amount);
            $wallet->increment('total_debited', $amount);

            return $this->record($user, $type, -$amount, 'success', $description, $meta, (float) $wallet->balance);
        }, 5);
    }

    public function hasBalance(User $user, float $amount): bool
    {
        return (float) $this->getOrCreateWallet($user)->balance >= $amount;
    }

    public function balance(User $user): float
    {
        return (float) $this->getOrCreateWallet($user)->balance;
    }

    public function record(User $user, string $type, float $amount, string $status, ?string $description, array $meta, float $balanceAfter): Transaction
    {
        return Transaction::create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'status' => $status,
            'reference' => $meta['reference'] ?? $this->reference(),
            'gateway' => $meta['gateway'] ?? null,
            'description' => $description,
            'meta' => $meta,
            'paid_at' => $status === 'success' ? now() : null,
        ]);
    }

    public function reference(string $prefix = 'LV'): string
    {
        return strtoupper($prefix.'-'.Str::random(12).now()->timestamp);
    }
}
