<?php

use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Spatie\Permission\Models\Role;

function makeInvoicedOrder(array $attributes = []): Order
{
    $order = Order::factory()->create($attributes);
    $order->invoice()->create([
        'invoice_number' => 'INV-TEST-'.$order->id,
        'issued_at' => now(),
    ]);

    return $order;
}

test('a guest holding the unguessable link can download a guest orders invoice', function () {
    $order = makeInvoicedOrder(['customer_id' => null]);

    $this->get(route('orders.invoice', $order))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

test('the owning customer can download their own invoice', function () {
    $customer = User::factory()->create();
    $order = makeInvoicedOrder(['customer_id' => $customer->id]);

    $this->actingAs($customer)->get(route('orders.invoice', $order))
        ->assertOk();
});

test('a different customer cannot download someone elses invoice', function () {
    $customer = User::factory()->create();
    $order = makeInvoicedOrder();

    $this->actingAs($customer)->get(route('orders.invoice', $order))
        ->assertForbidden();
});

test('an admin authenticated only on the admin guard can download any invoice', function () {
    (new RolePermissionSeeder)->run();

    $admin = User::factory()->admin()->create();
    $admin->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    $order = makeInvoicedOrder();

    // Deliberately bypass actingAs()/be(), which call Auth::shouldUse() and
    // would mask the real-world case: this route carries no auth:{guard}
    // middleware (guests must reach it too), so nothing flips the
    // request's default guard except the controller itself.
    $this->app['auth']->guard('admin')->setUser($admin);

    $this->get(route('orders.invoice', $order))->assertOk();
});

test('an admin without orders.view cannot download an invoice for someone elses order', function () {
    (new RolePermissionSeeder)->run();

    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Catalog Staff')->where('guard_name', 'admin')->first());

    $order = makeInvoicedOrder();

    $this->app['auth']->guard('admin')->setUser($staff);

    $this->get(route('orders.invoice', $order))->assertForbidden();
});
