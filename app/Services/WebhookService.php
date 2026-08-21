<?php

namespace App\Services;

use App\Jobs\ProcessPaymentWebhook;
use App\Models\Transaction;
use App\Models\WebhookLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Single entry point for payment gateway webhooks.
 *
 * Every delivery is audited to the webhook_logs table before processing, and
 * the controllers that expose these routes delegate straight to this service
 * so Paystack/Monnify specific logic is not duplicated.
 */
class WebhookService
{
    public function __construct(private PaymentService $payments) {}

    public function handle(string $gateway, Request $request): JsonResponse
    {
        if ($this->testMode($gateway)) {
            return response()->json(['status' => 'sandbox'], 200);
        }

        $payload = $request->json()->all();
        $content = $request->getContent();
        $signature = $request->header($this->signatureHeader($gateway));

        if (! $this->payments->verifyWebhookSignature($gateway, $content, $signature)) {
            $this->record($gateway, WebhookLog::STATUS_INVALID_SIGNATURE, null, 401, $payload, $request->ip());

            Log::channel('payments')->warning('Webhook rejected: invalid signature.', [
                'gateway' => $gateway,
                'ip' => $request->ip(),
            ]);

            return response()->json(['status' => 'invalid_signature'], 401);
        }

        $adapter = $this->payments->gateway($gateway);
        $event = $adapter->eventName($payload);
        $reference = $adapter->transactionReference($payload);

        if ($event !== $this->successEvent($gateway)) {
            $this->record($gateway, WebhookLog::STATUS_IGNORED, $reference, 200, $payload, $request->ip(), $event);

            return response()->json(['status' => 'ignored'], 200);
        }

        $pending = Transaction::query()
            ->where('reference', $reference)
            ->where('gateway', $gateway)
            ->where('status', Transaction::STATUS_PENDING)
            ->exists();

        if (! $pending) {
            $this->record($gateway, WebhookLog::STATUS_IGNORED, $reference, 200, $payload, $request->ip(), $event);

            return response()->json(['status' => 'ignored'], 200);
        }

        $log = $this->record($gateway, WebhookLog::STATUS_RECEIVED, $reference, 200, $payload, $request->ip(), $event);

        ProcessPaymentWebhook::dispatch($gateway, $payload, $log->id);

        Log::channel('payments')->info('Webhook accepted and queued.', [
            'gateway' => $gateway,
            'event' => $event,
            'reference' => $reference,
        ]);

        return response()->json(['status' => 'success'], 200);
    }

    /**
     * Record the settlement outcome on an already-logged webhook delivery.
     */
    public function markProcessed(?int $logId, string $result, string $paymentStatus): void
    {
        if ($logId === null) {
            return;
        }

        $status = $result === 'success' || $result === 'already_processed'
            ? WebhookLog::STATUS_PROCESSED
            : WebhookLog::STATUS_FAILED;

        WebhookLog::whereKey($logId)->update([
            'status' => $status,
            'processed_at' => now(),
        ]);
    }

    private function record(
        string $gateway,
        string $status,
        ?string $reference,
        int $responseStatus,
        array $payload,
        ?string $ip,
        ?string $event = null,
    ): WebhookLog {
        return WebhookLog::create([
            'gateway' => $gateway,
            'event' => $event,
            'reference' => $reference,
            'status' => $status,
            'response_status' => $responseStatus,
            'source_ip' => $ip,
            'payload' => $payload,
        ]);
    }

    private function successEvent(string $gateway): string
    {
        return $gateway === 'monnify' ? 'SUCCESSFUL_TRANSACTION' : 'charge.success';
    }

    private function signatureHeader(string $gateway): string
    {
        return $gateway === 'monnify' ? 'monnify-signature' : 'x-paystack-signature';
    }

    private function testMode(string $gateway): bool
    {
        return $gateway === 'monnify'
            ? PaymentSettings::monnifyTestMode()
            : PaymentSettings::paystackTestMode();
    }
}
