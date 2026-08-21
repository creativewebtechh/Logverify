<?php

namespace App\Livewire\Concerns;

use App\Services\PinService;

trait ConfirmsTransactionPin
{
    public string $pin = '';

    public bool $showPinModal = false;

    public ?string $pinModalTitle = null;

    public ?float $pinModalAmount = null;

    /**
     * Open the transaction-PIN confirmation modal.
     *
     * Returns false (and flashes a notice) when the user has not set a PIN yet,
     * so purchases stay blocked until one is configured.
     */
    protected function openPinModal(?float $amount = null, ?string $title = null): bool
    {
        if (! auth()->user()->hasPin()) {
            session()->flash('error', 'Set a transaction PIN in Account security before you can make purchases.');

            return false;
        }

        $this->pin = '';
        $this->pinModalAmount = $amount;
        $this->pinModalTitle = $title ?? 'Confirm purchase';
        $this->showPinModal = true;

        return true;
    }

    public function closePinModal(): void
    {
        $this->showPinModal = false;
        $this->pin = '';
        $this->pinModalTitle = null;
        $this->pinModalAmount = null;
    }

    /**
     * Validate the entered PIN against the user's stored (hashed) PIN,
     * applying the shared attempt limit and temporary lockout.
     */
    protected function verifyPin(): bool
    {
        $this->validate(['pin' => ['required', 'digits:4']]);

        $user = auth()->user();
        $service = app(PinService::class);

        if ($service->isLocked($user)) {
            $this->addError('pin', 'Too many failed attempts. Your transaction PIN is temporarily locked. Try again in '.$service->lockoutSecondsRemaining($user).' seconds.');

            return false;
        }

        if (! $service->verify($user, $this->pin)) {
            $remaining = $service->attemptsRemaining($user);

            if ($remaining <= 0) {
                $this->addError('pin', 'Too many failed attempts. Your transaction PIN is temporarily locked. Try again in '.$service->lockoutSecondsRemaining($user).' seconds.');
            } else {
                $this->addError('pin', 'That PIN is incorrect. '.$remaining.' attempt'.($remaining === 1 ? '' : 's').' remaining before your PIN is temporarily locked.');
            }

            return false;
        }

        $this->closePinModal();

        return true;
    }
}
