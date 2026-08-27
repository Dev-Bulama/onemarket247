<?php

use App\Enums\ShippingRateType;
use App\Enums\StockStatus;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ShippingRate;
use App\Models\ShippingZone;
use App\Models\ShippingZoneLocation;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Http;

function seedFreeShippingForApiTest(Country $country): void
{
    $zone = ShippingZone::factory()->create();
    ShippingZoneLocation::factory()->create(['shipping_zone_id' => $zone->id, 'country_id' => $country->id]);
    ShippingRate::factory()->create(['shipping_zone_id' => $zone->id, 'rate_type' => ShippingRateType::Free, 'base_amount' => 0]);
}

function addItemAndInitCheckout(?string $bearerToken = null): array
{
    Currency::factory()->create(['code' => 'NGN', 'is_default' => true]);
    $country = Country::factory()->create();
    seedFreeShippingForApiTest($country);
    $product = Product::factory()->create(['price' => 1000, 'manage_stock' => true, 'stock_status' => StockStatus::InStock]);
    $warehouse = Warehouse::factory()->create(['vendor_id' => $product->vendor_id]);
    app(\App\Actions\Inventory\AdjustStockAction::class)->handle($warehouse, $product, 10, 'seed');

    $headers = $bearerToken ? ['Authorization' => "Bearer {$bearerToken}"] : [];

    $add = test()->withHeaders($headers)
        ->postJson('/api/v1/cart/items', ['product_id' => $product->id, 'quantity' => 2])
        ->assertCreated();

    $guestToken = $add->json('data.guest_token');
    $cartTokenSuffix = $guestToken ? "?cart_token={$guestToken}" : '';

    $init = test()->withHeaders($headers)
        ->postJson("/api/v1/checkout/init{$cartTokenSuffix}")
        ->assertOk();

    return compact('country', 'product', 'guestToken', 'init', 'headers');
}

test('a guest can complete checkout end to end with bank transfer', function () {
    ['country' => $country, 'init' => $init, 'guestToken' => $guestToken] = addItemAndInitCheckout();

    $sessionKey = $init->json('data.checkout_session_key');

    $response = $this->postJson('/api/v1/checkout/complete', [
        'checkout_session_key' => $sessionKey,
        'cart_token' => $guestToken,
        'email' => 'guest@example.com',
        'full_name' => 'Jane Guest',
        'address_line_1' => '1 Main St',
        'country_id' => $country->id,
        'payment_method' => 'bank_transfer',
    ])->assertCreated();

    $response->assertJsonPath('data.payment.gateway', 'bank_transfer')
        ->assertJsonPath('data.bank_transfer.reference', $response->json('data.order_number'));

    $orderId = $response->json('data.id');

    $this->getJson("/api/v1/checkout/{$sessionKey}/status")
        ->assertOk()
        ->assertJsonPath('data.is_resolved', true)
        ->assertJsonPath('data.order.id', $orderId);
});

test('an authenticated customer\'s completed order is attached to their account', function () {
    $user = User::factory()->create();
    $token = $user->createToken('t', ['customer:*'])->plainTextToken;

    ['country' => $country, 'init' => $init] = addItemAndInitCheckout($token);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/checkout/complete', [
            'checkout_session_key' => $init->json('data.checkout_session_key'),
            'full_name' => $user->name,
            'address_line_1' => '1 Main St',
            'country_id' => $country->id,
            'payment_method' => 'bank_transfer',
        ])->assertCreated();

    $order = Order::where('public_id', $response->json('data.id'))->firstOrFail();
    expect($order->customer_id)->toBe($user->id);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/orders')
        ->assertOk()
        ->assertJsonPath('data.0.id', $order->public_id);
});

test('orders index requires authentication', function () {
    $this->getJson('/api/v1/orders')->assertUnauthorized();
});

test('a guest can view their own order via its public_id but not one belonging to someone else', function () {
    ['country' => $country, 'init' => $init, 'guestToken' => $guestToken] = addItemAndInitCheckout();

    $response = $this->postJson('/api/v1/checkout/complete', [
        'checkout_session_key' => $init->json('data.checkout_session_key'),
        'cart_token' => $guestToken,
        'email' => 'guest@example.com',
        'full_name' => 'Jane Guest',
        'address_line_1' => '1 Main St',
        'country_id' => $country->id,
        'payment_method' => 'bank_transfer',
    ])->assertCreated();

    $orderId = $response->json('data.id');

    $this->getJson("/api/v1/orders/{$orderId}")->assertOk()->assertJsonPath('data.id', $orderId);
    $this->getJson("/api/v1/orders/{$orderId}/track")->assertOk();
});

test('a customer cannot view another customer\'s order', function () {
    $owner = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $owner->id]);

    $intruder = User::factory()->create();
    $token = $intruder->createToken('t', ['customer:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/orders/{$order->public_id}")
        ->assertForbidden();
});

test('a customer can cancel their own pending order', function () {
    $user = User::factory()->create();
    $token = $user->createToken('t', ['customer:*'])->plainTextToken;

    ['country' => $country, 'init' => $init] = addItemAndInitCheckout($token);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/checkout/complete', [
            'checkout_session_key' => $init->json('data.checkout_session_key'),
            'full_name' => $user->name,
            'address_line_1' => '1 Main St',
            'country_id' => $country->id,
            'payment_method' => 'bank_transfer',
        ])->assertCreated();

    $orderId = $response->json('data.id');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/orders/{$orderId}/cancel", ['reason' => 'Changed my mind'])
        ->assertOk()
        ->assertJsonPath('data.vendor_orders.0.status', 'cancelled');
});

test('initializing paystack payment returns an authorization url, and verify marks the order paid', function () {
    PaymentGateway::factory()->create(['code' => 'paystack', 'secret_key' => 'sk_test_123']);
    $user = User::factory()->create();
    $token = $user->createToken('t', ['customer:*'])->plainTextToken;

    ['country' => $country, 'init' => $init] = addItemAndInitCheckout($token);

    $complete = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/checkout/complete', [
            'checkout_session_key' => $init->json('data.checkout_session_key'),
            'full_name' => $user->name,
            'address_line_1' => '1 Main St',
            'country_id' => $country->id,
            'payment_method' => 'paystack',
        ])->assertCreated();

    $orderId = $complete->json('data.id');
    $order = Order::where('public_id', $orderId)->firstOrFail();
    $payment = $order->payments()->first();

    Http::fake([
        '*/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/xyz', 'reference' => $payment->reference]]),
        '*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'reference' => $payment->reference, 'amount' => $payment->amount]]),
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/payments/{$orderId}/initialize")
        ->assertOk()
        ->assertJsonPath('data.authorization_url', 'https://checkout.paystack.com/xyz');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/payments/{$orderId}/verify")
        ->assertOk()
        ->assertJsonPath('data.status', 'paid');
});
