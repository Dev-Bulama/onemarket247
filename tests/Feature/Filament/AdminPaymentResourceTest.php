<?php

use App\Enums\PaymentStatus;
use App\Filament\Resources\PaymentGateways\Pages\EditPaymentGateway;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentGateway;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function financeStaffAdmin(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Finance Staff')->where('guard_name', 'admin')->first());

    return $user;
}

test('finance staff can load the payment index and view pages', function () {
    $admin = financeStaffAdmin();
    $order = Order::factory()->create();
    $payment = Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::Paid, 'gateway' => 'paystack', 'gateway_reference' => 'ref-1', 'paid_at' => now()]);

    $this->actingAs($admin, 'admin')->get('/admin/payments')->assertOk();
    $this->actingAs($admin, 'admin')->get("/admin/payments/{$payment->reference}")->assertOk();
});

test('a staff admin without payments.view cannot access payments', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Catalog Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/payments')->assertForbidden();
});

test('finance staff can rotate a gateway secret without ever seeing the current one', function () {
    $admin = financeStaffAdmin();
    $gateway = PaymentGateway::factory()->create(['secret_key' => 'sk_test_original']);

    $this->actingAs($admin, 'admin')
        ->get("/admin/payment-gateways/{$gateway->id}/edit")
        ->assertOk()
        ->assertDontSee('sk_test_original');

    Livewire::actingAs($admin, 'admin')
        ->test(EditPaymentGateway::class, ['record' => $gateway->getRouteKey()])
        ->fillForm(['secret_key' => 'sk_test_rotated'])
        ->call('save');

    expect($gateway->fresh()->secret_key)->toBe('sk_test_rotated');
});

test('leaving the secret field blank on save keeps the existing secret intact', function () {
    $admin = financeStaffAdmin();
    $gateway = PaymentGateway::factory()->create(['secret_key' => 'sk_test_original', 'is_active' => true]);

    Livewire::actingAs($admin, 'admin')
        ->test(EditPaymentGateway::class, ['record' => $gateway->getRouteKey()])
        ->fillForm(['is_active' => false])
        ->call('save');

    expect($gateway->fresh())
        ->is_active->toBeFalse()
        ->secret_key->toBe('sk_test_original');
});

test('finance staff can refund a paid payment from its view page', function () {
    $admin = financeStaffAdmin();
    PaymentGateway::factory()->create(['secret_key' => 'sk_test_123']);
    $order = Order::factory()->create();
    $payment = Payment::factory()->create(['order_id' => $order->id, 'amount' => 50000, 'status' => PaymentStatus::Paid, 'gateway' => 'paystack', 'gateway_reference' => 'ref-1', 'paid_at' => now()]);

    Http::fake(['*/refund' => Http::response(['status' => true, 'data' => []])]);

    Livewire::actingAs($admin, 'admin')
        ->test(ViewPayment::class, ['record' => $payment->getRouteKey()])
        ->callAction('refund', data: ['amount' => 500]);

    expect($payment->fresh())
        ->status->toBe(PaymentStatus::Refunded)
        ->refunded_amount->toBe(50000);
});

test('a pending (unpaid) payment cannot be refunded from its view page', function () {
    $admin = financeStaffAdmin();
    PaymentGateway::factory()->create();
    $order = Order::factory()->create();
    $payment = Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::Pending]);

    Livewire::actingAs($admin, 'admin')
        ->test(ViewPayment::class, ['record' => $payment->getRouteKey()])
        ->assertActionHidden('refund');
});

test('an admin without refunds.manage does not see the refund action even on a paid payment', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());
    $staff->givePermissionTo(Permission::findOrCreate('payments.view', 'admin'));

    PaymentGateway::factory()->create();
    $order = Order::factory()->create();
    $payment = Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::Paid, 'paid_at' => now()]);

    Livewire::actingAs($staff, 'admin')
        ->test(ViewPayment::class, ['record' => $payment->getRouteKey()])
        ->assertActionHidden('refund');
});
