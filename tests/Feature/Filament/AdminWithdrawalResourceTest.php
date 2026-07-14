<?php

use App\Enums\WithdrawalStatus;
use App\Filament\Resources\Withdrawals\Pages\ViewWithdrawal;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorWallet;
use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function financeAdminForWithdrawals(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Finance Staff')->where('guard_name', 'admin')->first());

    return $user;
}

function pendingWithdrawalFixture(): Withdrawal
{
    $vendor = Vendor::factory()->create();
    VendorWallet::factory()->create(['vendor_id' => $vendor->id, 'available_balance' => 0, 'reserved_balance' => 10000]);
    $method = WithdrawalMethod::factory()->create(['vendor_id' => $vendor->id]);

    return Withdrawal::factory()->create([
        'vendor_id' => $vendor->id,
        'withdrawal_method_id' => $method->id,
        'amount' => 10000,
        'status' => WithdrawalStatus::Pending,
    ]);
}

test('finance staff can load the withdrawals index and view pages', function () {
    $admin = financeAdminForWithdrawals();
    $withdrawal = pendingWithdrawalFixture();

    $this->actingAs($admin, 'admin')->get('/admin/withdrawals')->assertOk();
    $this->actingAs($admin, 'admin')->get("/admin/withdrawals/{$withdrawal->reference}")->assertOk();
});

test('an admin without withdrawals.view cannot access withdrawals', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Catalog Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/withdrawals')->assertForbidden();
});

test('finance staff can approve, then mark paid, a pending withdrawal', function () {
    $admin = financeAdminForWithdrawals();
    $withdrawal = pendingWithdrawalFixture();

    Livewire::actingAs($admin, 'admin')
        ->test(ViewWithdrawal::class, ['record' => $withdrawal->getRouteKey()])
        ->callAction('approve');

    expect($withdrawal->fresh())
        ->status->toBe(WithdrawalStatus::Approved)
        ->reviewed_by->toBe($admin->id);

    Livewire::actingAs($admin, 'admin')
        ->test(ViewWithdrawal::class, ['record' => $withdrawal->getRouteKey()])
        ->callAction('mark-paid');

    $wallet = VendorWallet::where('vendor_id', $withdrawal->vendor_id)->firstOrFail();
    expect($withdrawal->fresh()->status)->toBe(WithdrawalStatus::Paid)
        ->and($wallet->withdrawn_balance)->toBe(10000)
        ->and($wallet->reserved_balance)->toBe(0);
});

test('finance staff can reject a pending withdrawal with a reason', function () {
    $admin = financeAdminForWithdrawals();
    $withdrawal = pendingWithdrawalFixture();

    Livewire::actingAs($admin, 'admin')
        ->test(ViewWithdrawal::class, ['record' => $withdrawal->getRouteKey()])
        ->callAction('reject', data: ['reason' => 'Suspicious activity']);

    expect($withdrawal->fresh())
        ->status->toBe(WithdrawalStatus::Rejected)
        ->rejection_reason->toBe('Suspicious activity');

    $wallet = VendorWallet::where('vendor_id', $withdrawal->vendor_id)->firstOrFail();
    expect($wallet->available_balance)->toBe(10000)
        ->and($wallet->reserved_balance)->toBe(0);
});

test('the mark-paid action is not visible on a pending (not yet approved) withdrawal', function () {
    $admin = financeAdminForWithdrawals();
    $withdrawal = pendingWithdrawalFixture();

    Livewire::actingAs($admin, 'admin')
        ->test(ViewWithdrawal::class, ['record' => $withdrawal->getRouteKey()])
        ->assertActionHidden('mark-paid');
});

test('an admin without withdrawals.approve does not see the approve action', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());
    $staff->givePermissionTo(Permission::findOrCreate('withdrawals.view', 'admin'));

    $withdrawal = pendingWithdrawalFixture();

    Livewire::actingAs($staff, 'admin')
        ->test(ViewWithdrawal::class, ['record' => $withdrawal->getRouteKey()])
        ->assertActionHidden('approve');
});
