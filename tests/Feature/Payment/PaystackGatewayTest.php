<?php

use App\Exceptions\PaymentGatewayException;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Services\Payment\PaystackGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

test('initialize sends the order email, amount, and reference, and returns the authorization url', function () {
    PaymentGateway::factory()->create(['code' => 'paystack', 'secret_key' => 'sk_test_123']);

    $order = Order::factory()->create();
    $payment = Payment::factory()->create(['order_id' => $order->id, 'amount' => 50000]);

    Http::fake([
        '*/transaction/initialize' => Http::response([
            'status' => true,
            'data' => ['authorization_url' => 'https://checkout.paystack.com/abc123', 'access_code' => 'abc', 'reference' => $payment->reference],
        ]),
    ]);

    $result = app(PaystackGateway::class)->initialize($payment, 'https://example.test/callback');

    expect($result->authorizationUrl)->toBe('https://checkout.paystack.com/abc123')
        ->and($result->reference)->toBe($payment->reference);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/transaction/initialize')
        && $request->hasHeader('Authorization', 'Bearer sk_test_123')
        && $request['email'] === $order->customerEmail()
        && $request['amount'] === 50000
        && $request['reference'] === $payment->reference
        && $request['callback_url'] === 'https://example.test/callback');
});

test('initialize throws when paystack rejects the request', function () {
    PaymentGateway::factory()->create(['code' => 'paystack', 'secret_key' => 'sk_test_123']);
    $payment = Payment::factory()->create();

    Http::fake(['*/transaction/initialize' => Http::response(['status' => false, 'message' => 'Invalid key'], 401)]);

    expect(fn () => app(PaystackGateway::class)->initialize($payment, 'https://example.test/callback'))
        ->toThrow(PaymentGatewayException::class);
});

test('verify reports success only when the gateway says the transaction succeeded', function () {
    PaymentGateway::factory()->create(['code' => 'paystack', 'secret_key' => 'sk_test_123']);

    Http::fake([
        '*/transaction/verify/success-ref' => Http::response(['status' => true, 'data' => ['status' => 'success', 'reference' => 'success-ref', 'amount' => 50000, 'paid_at' => '2026-01-01T00:00:00.000Z']]),
        '*/transaction/verify/failed-ref' => Http::response(['status' => true, 'data' => ['status' => 'failed', 'reference' => 'failed-ref', 'amount' => 50000]]),
    ]);

    $success = app(PaystackGateway::class)->verify('success-ref');
    expect($success->successful)->toBeTrue()->and($success->amount)->toBe(50000);

    $failed = app(PaystackGateway::class)->verify('failed-ref');
    expect($failed->successful)->toBeFalse();
});

test('refund posts the transaction reference and amount', function () {
    PaymentGateway::factory()->create(['code' => 'paystack', 'secret_key' => 'sk_test_123']);

    Http::fake(['*/refund' => Http::response(['status' => true, 'data' => ['amount' => 20000]])]);

    $result = app(PaystackGateway::class)->refund('ref-1', 20000);

    expect($result->successful)->toBeTrue();
    Http::assertSent(fn ($request) => str_contains($request->url(), '/refund')
        && $request['transaction'] === 'ref-1'
        && $request['amount'] === 20000);
});

test('webhook signature verification matches a correctly signed body and rejects a tampered one', function () {
    PaymentGateway::factory()->create(['code' => 'paystack', 'webhook_secret' => 'whsec_123']);

    $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'abc123']]);
    $validSignature = hash_hmac('sha512', $body, 'whsec_123');

    $validRequest = Request::create('/webhook', 'POST', server: ['HTTP_X_PAYSTACK_SIGNATURE' => $validSignature], content: $body);
    $tamperedRequest = Request::create('/webhook', 'POST', server: ['HTTP_X_PAYSTACK_SIGNATURE' => $validSignature], content: $body.'x');
    $missingHeaderRequest = Request::create('/webhook', 'POST', content: $body);

    $gateway = app(PaystackGateway::class);

    expect($gateway->verifyWebhookSignature($validRequest))->toBeTrue()
        ->and($gateway->verifyWebhookSignature($tamperedRequest))->toBeFalse()
        ->and($gateway->verifyWebhookSignature($missingHeaderRequest))->toBeFalse();
});

test('eventIdFromWebhookPayload combines event type and reference for a stable dedupe key', function () {
    $gateway = app(PaystackGateway::class);

    $eventId = $gateway->eventIdFromWebhookPayload(['event' => 'charge.success', 'data' => ['reference' => 'abc123']]);
    expect($eventId)->toBe('charge.success:abc123');

    expect($gateway->eventIdFromWebhookPayload(['event' => 'charge.success']))->toBeNull();
});
