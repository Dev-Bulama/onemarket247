<?php

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;

test('the owning customer can view their order', function () {
    $order = Order::factory()->create();

    expect($order->customer->can('view', $order))->toBeTrue();
});

test('a different customer cannot view someone elses order', function () {
    $order = Order::factory()->create();
    $stranger = User::factory()->create();

    expect($stranger->can('view', $order))->toBeFalse();
});

test('an admin with orders.view can view any customer order', function () {
    Permission::findOrCreate('orders.view', 'web');

    $order = Order::factory()->create();
    $admin = User::factory()->create();
    $admin->givePermissionTo('orders.view');

    expect($admin->can('view', $order))->toBeTrue();
});

test('anyone can view a guest order since the unguessable link is the credential', function () {
    $order = Order::factory()->guest()->create();
    $stranger = User::factory()->create();

    expect($stranger->can('view', $order))->toBeTrue();
    expect(Gate::forUser(null)->allows('view', $order))->toBeTrue();
});

test('a guest (unauthenticated) cannot view a registered customers order', function () {
    $order = Order::factory()->create();

    expect(Gate::forUser(null)->allows('view', $order))->toBeFalse();
});
