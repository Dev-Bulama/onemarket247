<?php

use App\Models\Order;
use App\Models\User;

test('a guest can look up an order by order number and guest email', function () {
    $order = Order::factory()->create(['guest_email' => 'jane@example.com']);

    $this->get(route('pages.track-order', ['order_number' => $order->order_number, 'email' => 'jane@example.com']))
        ->assertOk()
        ->assertSee($order->order_number);
});

test('a customer can look up an order by order number and their account email', function () {
    $customer = User::factory()->create(['email' => 'jane@example.com']);
    $order = Order::factory()->create(['customer_id' => $customer->id, 'guest_email' => null]);

    $this->get(route('pages.track-order', ['order_number' => $order->order_number, 'email' => 'jane@example.com']))
        ->assertOk()
        ->assertSee($order->order_number);
});

test('a mismatched email does not reveal the order', function () {
    $order = Order::factory()->create(['guest_email' => 'jane@example.com']);

    $this->get(route('pages.track-order', ['order_number' => $order->order_number, 'email' => 'someone-else@example.com']))
        ->assertOk()
        ->assertSee('couldn\'t find', false)
        ->assertDontSee('Not yet shipped');
});

test('the track order form renders with no query params', function () {
    $this->get(route('pages.track-order'))->assertOk();
});
