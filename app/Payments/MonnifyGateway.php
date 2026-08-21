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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class MonnifyGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'monnify';
    }

    public function label(): string
    {
        return 'Monnify';
    }

    public function isConfigured(): bool
    {
        return filled(PaymentSettings::monnifyClientKey())
            && filled(PaymentSettings::monnifyClientSecret())
            && filled(PaymentSettings::monnifyContractCode());
    }

    public function isTestMode(): bool
    {
        return PaymentSettings::monnifyTestMode();
    }

    public function supportedMethods(): array
    {
        return [
            PaymentMethod::Card,
            PaymentMethod::BankTransfer,
            PaymentMethod::Ussd,
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

        $response = Http::withToken($this->token())
            ->acceptJson()
            ->retry(2, 100, fn (Throwable $e) => $this->retryTransient($e), false)
            ->post($this->baseUrl().'/api/v1/merchant/transactions/init-transaction', [
                'amount' => round($request->amount, 2),
                'customerName' => $request->user->name,
                'customerEmail' => $request->user->email,
                'paymentReference' => $request->reference,
                'paymentDescription' => 'Wallet funding',
                'currencyCode' => 'NGN',
                'contractCode' => (string) PaymentSettings::monnifyContractCode(),
                'redirectUrl' => $request->callbackUrl,
                'paymentMethods' => $this->monnifyMethods($request->method),
            ]);

        if ($response->failed() || ! ($response->json('requestSuccessful') ?? false)) {
            throw new PaymentException('Could not initialize payment. Please try again.');
        }

        $body = $response->json('responseBody', []);

        Log::channel('payments')->info('Monnify payment initialized.', [
            'reference' => $request->reference,
            'amount' => $request->amount,
            'method' => $request->method->value,
        ]);

        return new DepositIntent(
            gateway: $this->name(),
            redirectUrl: $body['checkoutUrl'] ?? null,
            sandbox: $this->isTestMode(),
            response: $body,
        );
    }

    public function verify(string $reference): PaymentNotification
    {
        if (! $this->isConfigured()) {
            throw new PaymentException('Monnify is not configured.');
        }

        $response = Http::withToken($this->token())
            ->acceptJson()
            ->retry(2, 100, fn (Throwable $e) => $this->retryTransient($e), false)
            ->get($this->baseUrl().'/api/v2/merchant/transactions/query', [
                'paymentReference' => $reference,
            ]);

        if ($response->failed() || ! ($response->json('requestSuccessful') ?? false)) {
            throw new PaymentException('Could not verify payment. Please try again.');
        }

        $body = $response->json('responseBody', []);

        $notification = $this->notificationFromBody($reference, $body);

        Log::channel('payments')->info('Monnify payment verified.', [
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

        $secret = PaymentSettings::monnifyClientSecret();

        if ($secret === null) {
            return false;
        }

        $expectedBase64 = base64_encode(hash_hmac('sha512', $payload, $secret, true));
        $expectedHex = hash_hmac('sha512', $payload, $secret);

        return hash_equals($expectedBase64, $signature) || hash_equals($expectedHex, $signature);
    }

    public function parseWebhook(array $payload): PaymentNotification
    {
        $data = $payload['eventData'] ?? [];
        $reference = (string) ($data['paymentReference'] ?? '');

        return $this->notificationFromBody($reference, $data);
    }

    public function eventName(array $payload): string
    {
        return (string) ($payload['eventType'] ?? '');
    }

    public function transactionReference(array $payload): ?string
    {
        $reference = $payload['eventData']['paymentReference'] ?? null;

        return $reference !== null ? (string) $reference : null;
    }

    private function notificationFromBody(string $reference, array $body): PaymentNotification
    {
        $status = match ($body['paymentStatus'] ?? '') {
            'PAID' => PaymentNotification::STATUS_SUCCESS,
            'FAILED', 'REVERSED', 'EXPIRED', 'CANCELLED' => PaymentNotification::STATUS_FAILED,
            default => PaymentNotification::STATUS_PENDING,
        };

        $amountPaid = (float) (($body['amountPaid'] ?? 0) / 100);
        $settlement = isset($body['settlementAmount']) ? (float) ($body['settlementAmount'] / 100) : null;

        return new PaymentNotification(
            gateway: $this->name(),
            reference: $reference,
            status: $status,
            amountPaid: $amountPaid,
            fee: $settlement !== null ? round(max($amountPaid - $settlement, 0), 2) : null,
            gatewayReference: isset($body['transactionReference']) ? (string) $body['transactionReference'] : null,
            currency: $body['currency'] ?? 'NGN',
            method: $this->methodFromType($body['paymentMethod'] ?? null),
            reason: $this->reasonForStatus($body['paymentStatus'] ?? null),
            raw: $body,
        );
    }

    private function baseUrl(): string
    {
        return (string) PaymentSettings::monnifyBaseUrl();
    }

    private function token(): string
    {
        return Cache::remember(
            'monnify.access_token',
            3000,
            fn () => $this->requestToken()
        );
    }

    /**
     * Monnify access tokens are valid for one hour; they are cached so the
     * per-transaction auth handshake does not become a bottleneck under load.
     */
    private function requestToken(): string
    {
        $basic = base64_encode(PaymentSettings::monnifyClientKey().':'.PaymentSettings::monnifyClientSecret());

        $response = Http::withHeaders(['Authorization' => 'Basic '.$basic])
            ->acceptJson()
            ->retry(3, 100, fn (Throwable $e) => $this->retryTransient($e), false)
            ->post($this->baseUrl().'/api/v1/auth/login');

        if ($response->failed() || empty($response->json('responseBody.accessToken'))) {
            Log::channel('payments')->error('Monnify token request failed.', [
                'status' => $response->status(),
            ]);

            throw new PaymentException('Could not authenticate with Monnify. Please check your credentials.');
        }

        $token = $response->json('responseBody.accessToken');

        Log::channel('payments')->info('Monnify access token refreshed.');

        return $token;
    }

    private function reasonForStatus(?string $status): ?string
    {
        return match ($status) {
            'CANCELLED', 'EXPIRED' => PaymentNotification::REASON_CANCELLED,
            'FAILED', 'REVERSED' => PaymentNotification::REASON_FAILED,
            default => null,
        };
    }

    /** @return string[] */
    private function monnifyMethods(PaymentMethod $method): array
    {
        return match ($method) {
            PaymentMethod::Card => ['CARD'],
            PaymentMethod::BankTransfer => ['ACCOUNT_TRANSFER'],
            PaymentMethod::Ussd => ['USSD'],
            default => ['CARD'],
        };
    }

    private function methodFromType(?string $type): ?PaymentMethod
    {
        return match (strtoupper((string) $type)) {
            'CARD' => PaymentMethod::Card,
            'ACCOUNT_TRANSFER' => PaymentMethod::BankTransfer,
            'USSD' => PaymentMethod::Ussd,
            'PHONE_NUMBER' => PaymentMethod::MobileMoney,
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
