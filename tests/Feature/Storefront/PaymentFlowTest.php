<?php

use App\Enums\PaymentStatus;
use App\Enums\VendorOrderStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Models\VendorOrder;
use Illuminate\Support\Facades\Http;

test('the confirmation page offers to pay when the order is still awaiting payment', function () {
    PaymentGateway::factory()->create(['code' => 'paystack', 'secret_key' => 'sk_test_123']);
    $order = Order::factory()->guest()->create(['total' => 50000]);
    VendorOrder::factory()->create(['order_id' => $order->id, 'status' => VendorOrderStatus::PendingPayment]);
    Payment::factory()->create(['order_id' => $order->id, 'amount' => 50000, 'status' => PaymentStatus::Pending]);

    $this->get(route('checkout.confirmation', $order))
        ->assertOk()
        ->assertSee('Pay now');
});

test('paying redirects to the gateway, and the callback marks the order paid', function () {
    PaymentGateway::factory()->create(['code' => 'paystack', 'secret_key' => 'sk_test_123']);
    $order = Order::factory()->guest()->create(['total' => 50000]);
    VendorOrder::factory()->create(['order_id' => $order->id, 'status' => VendorOrderStatus::PendingPayment]);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'amount' => 50000, 'status' => PaymentStatus::Pending]);

    Http::fake([
        '*/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/xyz', 'reference' => $payment->reference]]),
        '*/transaction/verify/*' => Http::response(['status' => true, 'data' => ['status' => 'success', 'reference' => $payment->reference, 'amount' => 50000]]),
    ]);

    $this->post(route('checkout.payment.initialize', $order))
        ->assertRedirect('https://checkout.paystack.com/xyz');

    expect($payment->fresh()->status)->toBe(PaymentStatus::Processing);

    $this->get(route('checkout.payment.callback', $order))
        ->assertRedirect(route('checkout.confirmation', $order));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid);

    $this->get(route('checkout.confirmation', $order))
        ->assertOk()
        ->assertSee('Payment received');
});

test('the confirmation page offers a retry after a failed payment attempt', function () {
    PaymentGateway::factory()->create(['code' => 'paystack', 'secret_key' => 'sk_test_123']);
    $order = Order::factory()->guest()->create(['total' => 50000]);
    VendorOrder::factory()->create(['order_id' => $order->id, 'status' => VendorOrderStatus::PendingPayment]);
    Payment::factory()->create(['order_id' => $order->id, 'amount' => 50000, 'gateway' => 'paystack', 'gateway_reference' => 'ref-1', 'status' => PaymentStatus::Failed, 'failed_at' => now()]);

    $this->get(route('checkout.confirmation', $order))
        ->assertOk()
        ->assertSee('Try again');
});

test('a different customer cannot pay for someone elses order', function () {
    PaymentGateway::factory()->create(['code' => 'paystack', 'secret_key' => 'sk_test_123']);
    $order = Order::factory()->create();
    Payment::factory()->create(['order_id' => $order->id, 'amount' => 50000, 'status' => PaymentStatus::Pending]);

    $otherCustomer = User::factory()->create();

    $this->actingAs($otherCustomer)->post(route('checkout.payment.initialize', $order))->assertForbidden();
});

test('a guest can initialize and complete payment on their own guest order', function () {
    PaymentGateway::factory()->create(['code' => 'paystack', 'secret_key' => 'sk_test_123']);
    $order = Order::factory()->guest()->create(['total' => 50000]);
    $payment = Payment::factory()->create(['order_id' => $order->id, 'amount' => 50000, 'status' => PaymentStatus::Pending]);

    Http::fake(['*/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/xyz', 'reference' => $payment->reference]])]);

    $this->post(route('checkout.payment.initialize', $order))->assertRedirect('https://checkout.paystack.com/xyz');
});
