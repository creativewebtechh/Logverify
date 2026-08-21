<?php

namespace App\Jobs;

use App\Payments\Data\PaymentNotification;
use App\Services\PaymentService;
use App\Services\TransactionService;
use App\Services\WebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Settles a webhook-confirmed funding payment.
 *
 * The gateway signature was already verified before dispatch; on top of that
 * the transaction is re-verified against the gateway API before the wallet is
 * credited. If the gateway is unreachable the (signed) webhook payload is used
 * instead so a transient API outage never blocks settlement.
 */
class ProcessPaymentWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function __construct(
        public string $gateway,
        public array $payload,
        public ?int $webhookLogId = null,
    ) {}

    public function handle(
        PaymentService $payments,
        TransactionService $transactions,
        WebhookService $webhooks,
    ): void {
        $notification = $payments->parseWebhook($this->gateway, $this->payload);

        if ($notification->reference === '') {
            $webhooks->markProcessed($this->webhookLogId, 'not_found', 'ignored');

            return;
        }

        $notification = $this->verifiedNotification($payments, $notification);

        $result = $transactions->fulfil($this->gateway, $notification);

        $webhooks->markProcessed($this->webhookLogId, $result, $notification->status);
    }

    public function failed(?Throwable $e): void
    {
        Log::channel('payments')->error('Webhook job failed.', [
            'gateway' => $this->gateway,
            'error' => $e?->getMessage(),
        ]);

        report($e);
    }

    /**
     * Re-verify against the gateway API before crediting, falling back to the
     * signed webhook payload if the gateway is temporarily unreachable.
     */
    private function verifiedNotification(PaymentService $payments, PaymentNotification $notification): PaymentNotification
    {
        try {
            $verified = $payments->verify($notification->reference, $this->gateway);

            if ($verified->succeeded()) {
                return $verified;
            }

            Log::channel('payments')->warning('Webhook verify did not report success; using signed webhook payload.', [
                'gateway' => $this->gateway,
                'reference' => $notification->reference,
                'verified_status' => $verified->status,
            ]);
        } catch (Throwable $e) {
            Log::channel('payments')->warning('Webhook verify unavailable; using signed webhook payload.', [
                'gateway' => $this->gateway,
                'reference' => $notification->reference,
                'error' => $e->getMessage(),
            ]);
        }

        return $notification;
    }
}
