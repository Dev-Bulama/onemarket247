<?php

use App\Actions\Inventory\AdjustStockAction;
use App\Actions\Inventory\ReserveStockAction;
use App\Actions\Payment\HandlePaymentWebhookAction;
use App\Actions\Payment\InitializePaymentAction;
use App\Actions\Payment\RefundPaymentAction;
use App\Actions\Payment\VerifyPaymentAction;
use App\Enums\PaymentLogDirection;
use App\Enums\PaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Exceptions\InvalidWebhookSignatureException;
use App\Exceptions\PaymentGatewayException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\PaymentLog;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WebhookEvent;
use App\Notifications\VendorOrderStatusUpdatedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

function setUpPendingOrder(int $total = 50000): array
{
    PaymentGateway::factory()->create(['code' => 'paystack', 'secret_key' => 'sk_test_123', 'webhook_secret' => 'whsec_123']);

    $vendor = Vendor::factory()->create();
    $warehouse = Warehouse::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id, 'manage_stock' => true]);
    app(AdjustStockAction::class)->handle($warehouse, $product, 10, 'seed');

    $order = Order::factory()->create(['total' => $total]);
    $vendorOrder = VendorOrder::factory()->create(['order_id' => $order->id, 'vendor_id' => $vendor->id, 'status' => VendorOrderStatus::PendingPayment]);
    OrderItem::factory()->create(['vendor_order_id' => $vendorOrder->id, 'product_id' => $product->id, 'warehouse_id' => $warehouse->id, 'quantity' => 3]);
    app(ReserveStockAction::class)->handle($warehouse, $product, 3, null, $vendorOrder);

    $payment = Payment::factory()->create(['order_id' => $order->id, 'amount' => $total, 'status' => PaymentStatus::Pending]);

    return compact('order', 'vendorOrder', 'payment', 'product', 'warehouse');
}

test('initializing a payment logs the request/response and moves it to processing', function () {
    ['order' => $order, 'payment' => $payment] = setUpPendingOrder();

    Http::fake(['*/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/x', 'reference' => $payment->reference]])]);

    $result = app(InitializePaymentAction::class)->handle($order, 'https://example.test/callback');

    expect($result['authorization_url'])->toBe('https://checkout.paystack.com/x')
        ->and($result['payment']->status)->toBe(PaymentStatus::Processing)
        ->and($result['payment']->gateway)->toBe('paystack')
        ->and($result['payment']->gateway_reference)->toBe($payment->reference);

    expect(PaymentLog::where('payment_id', $payment->id)->count())->toBe(2);
});

test('initializing an already-paid order is rejected', function () {
    ['order' => $order, 'payment' => $payment] = setUpPendingOrder();
    $payment->update(['status' => PaymentStatus::Paid]);

    expect(fn () => app(InitializePaymentAction::class)->handle($order, 'https://example.test/callback'))
        ->toThrow(PaymentGatewayException::class);
});

test('retrying after a failed payment creates a fresh payment row rather than reusing the old reference', function () {
    ['order' => $order, 'payment' => $payment] = setUpPendingOrder();
    $payment->update(['gateway' => 'paystack', 'gateway_reference' => $payment->reference, 'status' => PaymentStatus::Failed, 'failed_at' => now()]);

    Http::fake(['*/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/y', 'reference' => 'a-new-reference']])]);

    $result = app(InitializePaymentAction::class)->handle($order, 'https://example.test/callback');

    expect($result['payment']->id)->not->toBe($payment->id)
        ->and($order->payments()->count())->toBe(2);
});

test('verifying a successful payment confirms vendor orders, deducts stock, and notifies the customer', function () {
    Notification::fake();

    ['order' => $order, 'vendorOrder' => $vendorOrder, 'payment' => $payment, 'product' => $product, 'warehouse' => $warehouse] = setUpPendingOrder();
    $payment->update(['gateway' => 'paystack', 'gateway_reference' => $payment->reference, 'status' => PaymentStatus::Processing]);

    Http::fake(['*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'reference' => $payment->reference, 'amount' => 50000, 'paid_at' => now()->toIso8601String()]])]);

    $verified = app(VerifyPaymentAction::class)->handle($payment);

    expect($verified->status)->toBe(PaymentStatus::Paid)
        ->and($verified->paid_at)->not->toBeNull();

    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::Confirmed);

    $stock = WarehouseStock::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->firstOrFail();
    expect($stock->reserved)->toBe(0)->and($stock->on_hand)->toBe(7);

    Notification::assertSentTo($order->customer, VendorOrderStatusUpdatedNotification::class);
});

test('verifying is idempotent: a second call after payment is already paid does not re-deduct stock', function () {
    ['order' => $order, 'payment' => $payment, 'product' => $product, 'warehouse' => $warehouse] = setUpPendingOrder();
    $payment->update(['gateway' => 'paystack', 'gateway_reference' => $payment->reference, 'status' => PaymentStatus::Processing]);

    Http::fake(['*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'reference' => $payment->reference, 'amount' => 50000]])]);

    $first = app(VerifyPaymentAction::class)->handle($payment);
    $second = app(VerifyPaymentAction::class)->handle($first);

    expect($second->status)->toBe(PaymentStatus::Paid);

    $stock = WarehouseStock::where('warehouse_id', $warehouse->id)->where('product_id', $product->id)->firstOrFail();
    expect($stock->on_hand)->toBe(7);
});

test('a failed verification marks the payment failed and leaves the vendor order unconfirmed', function () {
    ['vendorOrder' => $vendorOrder, 'payment' => $payment] = setUpPendingOrder();
    $payment->update(['gateway' => 'paystack', 'gateway_reference' => $payment->reference, 'status' => PaymentStatus::Processing]);

    Http::fake(['*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'failed', 'reference' => $payment->reference, 'amount' => 50000]])]);

    $verified = app(VerifyPaymentAction::class)->handle($payment);

    expect($verified->status)->toBe(PaymentStatus::Failed)
        ->and($verified->failed_at)->not->toBeNull();
    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::PendingPayment);
});

test('a gateway-reported success with a mismatched amount is treated as failed, not paid', function () {
    ['vendorOrder' => $vendorOrder, 'payment' => $payment] = setUpPendingOrder(50000);
    $payment->update(['gateway' => 'paystack', 'gateway_reference' => $payment->reference, 'status' => PaymentStatus::Processing]);

    // Gateway claims success but for a different (tampered/lower) amount.
    Http::fake(['*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'reference' => $payment->reference, 'amount' => 100]])]);

    $verified = app(VerifyPaymentAction::class)->handle($payment);

    expect($verified->status)->toBe(PaymentStatus::Failed);
    expect($vendorOrder->fresh()->status)->toBe(VendorOrderStatus::PendingPayment);
});

test('a webhook with a valid signature verifies the matching payment exactly once, even if delivered twice', function () {
    ['vendorOrder' => $vendorOrder, 'payment' => $payment] = setUpPendingOrder();
    $payment->update(['gateway' => 'paystack', 'gateway_reference' => $payment->reference, 'status' => PaymentStatus::Processing]);

    Http::fake(['*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'reference' => $payment->reference, 'amount' => 50000]])]);

    $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => $payment->reference]]);
    $signature = hash_hmac('sha512', $body, 'whsec_123');
    $makeRequest = fn () => Request::create('/api/v1/webhooks/payments/paystack', 'POST', server: ['HTTP_X_PAYSTACK_SIGNATURE' => $signature], content: $body);

    app(HandlePaymentWebhookAction::class)->handle($makeRequest());
    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid);

    app(HandlePaymentWebhookAction::class)->handle($makeRequest());

    expect(WebhookEvent::count())->toBe(1);
    Http::assertSentCount(1);
});

test('a webhook with an invalid signature is rejected and logged', function () {
    ['payment' => $payment] = setUpPendingOrder();

    $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => $payment->reference]]);
    $request = Request::create('/api/v1/webhooks/payments/paystack', 'POST', server: ['HTTP_X_PAYSTACK_SIGNATURE' => 'not-valid'], content: $body);

    expect(fn () => app(HandlePaymentWebhookAction::class)->handle($request))
        ->toThrow(InvalidWebhookSignatureException::class);

    expect(PaymentLog::where('direction', PaymentLogDirection::Error)->exists())->toBeTrue();
});

test('a paid payment can be partially and then fully refunded', function () {
    ['payment' => $payment] = setUpPendingOrder();
    $payment->update(['gateway' => 'paystack', 'gateway_reference' => $payment->reference, 'status' => PaymentStatus::Paid, 'paid_at' => now()]);

    Http::fake(['*/refund' => Http::response(['status' => true, 'data' => []])]);

    $partial = app(RefundPaymentAction::class)->handle($payment, 20000);
    expect($partial->status)->toBe(PaymentStatus::PartiallyRefunded)
        ->and($partial->refunded_amount)->toBe(20000);

    $full = app(RefundPaymentAction::class)->handle($partial, 30000);
    expect($full->status)->toBe(PaymentStatus::Refunded)
        ->and($full->refunded_amount)->toBe(50000);
});

test('refunding more than the remaining balance is rejected', function () {
    ['payment' => $payment] = setUpPendingOrder();
    $payment->update(['gateway' => 'paystack', 'gateway_reference' => $payment->reference, 'status' => PaymentStatus::Paid, 'paid_at' => now(), 'refunded_amount' => 40000]);

    expect(fn () => app(RefundPaymentAction::class)->handle($payment, 20000))
        ->toThrow(PaymentGatewayException::class);
});

test('refunding a payment that was never paid is rejected', function () {
    ['payment' => $payment] = setUpPendingOrder();

    expect(fn () => app(RefundPaymentAction::class)->handle($payment, 100))
        ->toThrow(PaymentGatewayException::class);
});
