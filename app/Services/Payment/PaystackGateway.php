<?php

namespace App\Services\Payment;

use App\Exceptions\PaymentGatewayException;
use App\Models\Payment;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

/**
 * Raw HTTP integration against Paystack's REST API rather than a
 * third-party SDK — full control over logging/testability (Http::fake())
 * for exactly the guarantees this phase's gate cares about: server-side
 * amount computation, server-to-server verification, and signature
 * checking (see docs/architecture/10-security-architecture.md
 * "Payment Security").
 */
class PaystackGateway
{
    public const CODE = 'paystack';

    public function initialize(Payment $payment, string $callbackUrl): PaymentInitializationResult
    {
        $gateway = $this->activeGateway();

        $response = Http::withToken($gateway->secret_key)
            ->baseUrl($this->baseUrl())
            ->post('/transaction/initialize', [
                'email' => $payment->order->customerEmail(),
                'amount' => $payment->amount,
                'reference' => $payment->reference,
                'callback_url' => $callbackUrl,
            ]);

        if (! $response->successful() || ! ($response->json('status') === true)) {
            throw new PaymentGatewayException('Paystack rejected the initialize request: '.$response->json('message', $response->body()));
        }

        return new PaymentInitializationResult(
            authorizationUrl: $response->json('data.authorization_url'),
            reference: $response->json('data.reference'),
            raw: $response->json() ?? [],
        );
    }

    public function verify(string $reference): PaymentVerificationResult
    {
        $gateway = $this->activeGateway();

        $response = Http::withToken($gateway->secret_key)
            ->baseUrl($this->baseUrl())
            ->get('/transaction/verify/'.$reference);

        if (! $response->successful() || ! ($response->json('status') === true)) {
            throw new PaymentGatewayException('Paystack rejected the verify request: '.$response->json('message', $response->body()));
        }

        $data = $response->json('data', []);
        $transactionStatus = $data['status'] ?? null;

        return new PaymentVerificationResult(
            successful: $transactionStatus === 'success',
            reference: $data['reference'] ?? $reference,
            amount: (int) ($data['amount'] ?? 0),
            paidAt: filled($data['paid_at'] ?? null) ? Carbon::parse($data['paid_at']) : null,
            gatewayMessage: $data['gateway_response'] ?? null,
            raw: $response->json() ?? [],
        );
    }

    public function refund(string $reference, int $amount): RefundResult
    {
        $gateway = $this->activeGateway();

        $response = Http::withToken($gateway->secret_key)
            ->baseUrl($this->baseUrl())
            ->post('/refund', [
                'transaction' => $reference,
                'amount' => $amount,
            ]);

        if (! $response->successful() || ! ($response->json('status') === true)) {
            throw new PaymentGatewayException('Paystack rejected the refund request: '.$response->json('message', $response->body()));
        }

        return new RefundResult(
            successful: true,
            amount: $amount,
            raw: $response->json() ?? [],
        );
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $gateway = $this->activeGateway();

        if (blank($gateway->webhook_secret)) {
            return false;
        }

        $signature = $request->header('x-paystack-signature');

        if (blank($signature)) {
            return false;
        }

        $expected = hash_hmac('sha512', $request->getContent(), $gateway->webhook_secret);

        return hash_equals($expected, $signature);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function eventIdFromWebhookPayload(array $payload): ?string
    {
        $event = $payload['event'] ?? null;
        $reference = $payload['data']['reference'] ?? null;

        if (blank($event) || blank($reference)) {
            return null;
        }

        return "{$event}:{$reference}";
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function referenceFromWebhookPayload(array $payload): ?string
    {
        return $payload['data']['reference'] ?? null;
    }

    private function activeGateway(): PaymentGateway
    {
        $gateway = PaymentGateway::where('code', self::CODE)->where('is_active', true)->first();

        if (! $gateway || blank($gateway->secret_key)) {
            throw new PaymentGatewayException('Paystack is not configured. Set it up via PaymentGatewayResource.');
        }

        return $gateway;
    }

    private function baseUrl(): string
    {
        return config('services.paystack.base_url', 'https://api.paystack.co');
    }
}
