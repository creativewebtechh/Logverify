<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class PinService
{
    public const MAX_ATTEMPTS = 5;

    public const LOCKOUT_SECONDS = 900;

    public function hasPin(User $user): bool
    {
        return $user->hasPin();
    }

    /**
     * Verify a submitted transaction PIN against the stored (hashed) PIN.
     * Failed attempts are tracked and the PIN is temporarily locked after
     * repeated failures. Returns false for an unknown/incorrect PIN.
     */
    public function verify(User $user, ?string $pin): bool
    {
        if ($this->isLocked($user)) {
            return false;
        }

        $valid = $pin !== null
            && $user->transaction_pin !== null
            && Hash::check($pin, $user->transaction_pin);

        if (! $valid) {
            $this->registerFailure($user);
        }

        return $valid;
    }

    public function set(User $user, string $pin): void
    {
        $user->forceFill(['transaction_pin' => $pin])->save();

        $this->reset($user);
    }

    public function isLocked(User $user): bool
    {
        $state = $this->state($user);

        return $state['locked_until'] !== null && $state['locked_until']->isFuture();
    }

    public function lockoutSecondsRemaining(User $user): int
    {
        $lockedUntil = $this->state($user)['locked_until'];

        if ($lockedUntil === null || $lockedUntil->isPast()) {
            return 0;
        }

        return max(0, $lockedUntil->timestamp - now()->timestamp);
    }

    public function attemptsRemaining(User $user): int
    {
        $attempts = $this->state($user)['attempts'];

        return max(0, self::MAX_ATTEMPTS - $attempts);
    }

    public function reset(User $user): void
    {
        Cache::forget($this->key($user));
    }

    /**
     * @return array{attempts: int, locked_until: Carbon|null}
     */
    private function state(User $user): array
    {
        $state = Cache::get($this->key($user), ['attempts' => 0, 'locked_until' => null]);

        if ($state['locked_until'] !== null && $state['locked_until']->isPast()) {
            $state = ['attempts' => 0, 'locked_until' => null];
            Cache::put($this->key($user), $state, self::LOCKOUT_SECONDS);
        }

        return $state;
    }

    private function registerFailure(User $user): void
    {
        $state = $this->state($user);
        $state['attempts']++;

        if ($state['attempts'] >= self::MAX_ATTEMPTS) {
            $state['locked_until'] = now()->addSeconds(self::LOCKOUT_SECONDS);
        }

        Cache::put($this->key($user), $state, self::LOCKOUT_SECONDS);
    }

    private function key(User $user): string
    {
        return 'pin.attempts.'.$user->id;
    }
}
