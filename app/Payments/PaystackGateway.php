<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGateway;
use App\Payments\Data\DepositIntent;
use App\Payments\Data\DepositRequest;
use App\Payments\Data\PaymentNotification;
use App\Payments\Exceptions\PaymentException;
use App\Services\PaymentSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaystackGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'paystack';
    }

    public function label(): string
    {
        return 'Paystack';
    }

    public function publicKey(): ?string
    {
        return PaymentSettings::paystackPublicKey();
    }

    public function isConfigured(): bool
    {
        return filled(PaymentSettings::paystackSecretKey());
    }

    public function isTestMode(): bool
    {
        return PaymentSettings::paystackTestMode();
    }

    public function supportedMethods(): array
    {
        return [
            PaymentMethod::Card,
            PaymentMethod::BankTransfer,
            PaymentMethod::Ussd,
            PaymentMethod::Qr,
            PaymentMethod::MobileMoney,
        ];
    }

    public function supportsMethod(PaymentMethod $method): bool
    {
        return in_array($method, $this->supportedMethods(), true);
    }

    public function initialize(DepositRequest $request): DepositIntent
    {
        if (! $this->isConfigured()) {
            return new DepositIntent($this->name(), sandbox: true);
        }

        $response = Http::withToken((string) PaymentSettings::paystackSecretKey())
            ->acceptJson()
            ->retry(3, 100, fn (Throwable $e) => $this->retryTransient($e), false)
            ->post($this->baseUrl().'/transaction/initialize', [
                'email' => $request->user->email,
                'amount' => (int) round($request->amount * 100),
                'currency' => 'NGN',
                'reference' => $request->reference,
                'callback_url' => $request->callbackUrl,
                'channels' => $this->paystackChannels($request->method),
            ]);

        if ($response->failed() || ! ($response->json('status') ?? false)) {
            throw new PaymentException('Could not initialize payment. Please try again.');
        }

        $data = $response->json('data', []);

        Log::channel('payments')->info('Paystack payment initialized.', [
            'reference' => $request->reference,
            'amount' => $request->amount,
            'method' => $request->method->value,
            'access_code' => $data['access_code'] ?? null,
        ]);

        return new DepositIntent(
            gateway: $this->name(),
            redirectUrl: $data['authorization_url'] ?? null,
            accessCode: $data['access_code'] ?? null,
            sandbox: $this->isTestMode(),
            response: $data,
        );
    }

    public function verify(string $reference): PaymentNotification
    {
        if (! $this->isConfigured()) {
            throw new PaymentException('Paystack is not configured.');
        }

        $response = Http::withToken((string) PaymentSettings::paystackSecretKey())
            ->acceptJson()
            ->retry(3, 100, fn (Throwable $e) => $this->retryTransient($e), false)
            ->get($this->baseUrl().'/transaction/verify/'.rawurlencode($reference));

        if ($response->failed() || ! ($response->json('status') ?? false)) {
            throw new PaymentException('Could not verify payment. Please try again.');
        }

        $data = $response->json('data', []);

        $notification = new PaymentNotification(
            gateway: $this->name(),
            reference: $reference,
            status: match ($data['status'] ?? '') {
                'success' => PaymentNotification::STATUS_SUCCESS,
                'failed', 'abandoned' => PaymentNotification::STATUS_FAILED,
                default => PaymentNotification::STATUS_PENDING,
            },
            amountPaid: (float) (($data['amount'] ?? 0) / 100),
            fee: isset($data['fees']) ? (float) ($data['fees'] / 100) : null,
            gatewayReference: isset($data['id']) ? (string) $data['id'] : null,
            currency: $data['currency'] ?? 'NGN',
            method: $this->methodFromChannel($data['channel'] ?? null),
            reason: $this->reasonForStatus($data['status'] ?? null),
            raw: $data,
        );

        Log::channel('payments')->info('Paystack payment verified.', [
            'reference' => $reference,
            'status' => $notification->status,
            'amount_paid' => $notification->amountPaid,
        ]);

        return $notification;
    }

    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        if ($signature === null) {
            return false;
        }

        $secret = PaymentSettings::paystackWebhookSecret() ?: PaymentSettings::paystackSecretKey();

        if ($secret === null) {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $payload, $secret), $signature);
    }

    public function parseWebhook(array $payload): PaymentNotification
    {
        $data = $payload['data'] ?? [];
        $event = $payload['event'] ?? null;

        $status = match ($event) {
            'charge.success' => PaymentNotification::STATUS_SUCCESS,
            'charge.abandoned', 'charge.failed' => PaymentNotification::STATUS_FAILED,
            default => PaymentNotification::STATUS_PENDING,
        };

        $reason = match ($event) {
            'charge.abandoned' => PaymentNotification::REASON_CANCELLED,
            'charge.failed' => PaymentNotification::REASON_FAILED,
            default => null,
        };

        return new PaymentNotification(
            gateway: $this->name(),
            reference: (string) ($data['reference'] ?? ''),
            status: $status,
            amountPaid: (float) (($data['amount'] ?? 0) / 100),
            fee: isset($data['fees']) ? (float) ($data['fees'] / 100) : null,
            gatewayReference: isset($data['id']) ? (string) $data['id'] : null,
            currency: $data['currency'] ?? 'NGN',
            method: $this->methodFromChannel($data['channel'] ?? null),
            reason: $reason,
            raw: $data,
        );
    }

    public function eventName(array $payload): string
    {
        return (string) ($payload['event'] ?? '');
    }

    public function transactionReference(array $payload): ?string
    {
        $reference = $payload['data']['reference'] ?? null;

        return $reference !== null ? (string) $reference : null;
    }

    private function reasonForStatus(?string $status): ?string
    {
        return match ($status) {
            'abandoned' => PaymentNotification::REASON_CANCELLED,
            'failed' => PaymentNotification::REASON_FAILED,
            default => null,
        };
    }

    private function baseUrl(): string
    {
        return 'https://api.paystack.co';
    }

    /** @return string[] */
    private function paystackChannels(PaymentMethod $method): array
    {
        return match ($method) {
            PaymentMethod::Card => ['card'],
            PaymentMethod::BankTransfer => ['bank_transfer'],
            PaymentMethod::Ussd => ['ussd'],
            PaymentMethod::Qr => ['qr'],
            PaymentMethod::MobileMoney => ['mobile_money'],
        };
    }

    private function methodFromChannel(?string $channel): ?PaymentMethod
    {
        return match ($channel) {
            'card' => PaymentMethod::Card,
            'bank_transfer', 'bank' => PaymentMethod::BankTransfer,
            'ussd' => PaymentMethod::Ussd,
            'qr' => PaymentMethod::Qr,
            'mobile_money' => PaymentMethod::MobileMoney,
            default => null,
        };
    }

    private function retryTransient(Throwable $e): bool
    {
        if ($e instanceof ConnectionException) {
            return true;
        }

        if ($e instanceof RequestException) {
            return in_array($e->response?->status(), [408, 425, 429, 500, 502, 503, 504], true);
        }

        return false;
    }
}
