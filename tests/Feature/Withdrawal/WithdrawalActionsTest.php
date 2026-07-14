<?php

use App\Actions\Withdrawal\ApproveWithdrawalAction;
use App\Actions\Withdrawal\CancelWithdrawalAction;
use App\Actions\Withdrawal\MarkWithdrawalPaidAction;
use App\Actions\Withdrawal\RejectWithdrawalAction;
use App\Actions\Withdrawal\RequestWithdrawalAction;
use App\Enums\WithdrawalStatus;
use App\Exceptions\InsufficientWalletBalanceException;
use App\Exceptions\InvalidWithdrawalTransitionException;
use App\Models\Setting;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorWallet;
use App\Models\WithdrawalMethod;

function vendorWithAvailableBalance(int $available): array
{
    $vendor = Vendor::factory()->create();
    $wallet = VendorWallet::factory()->create(['vendor_id' => $vendor->id, 'available_balance' => $available]);
    $method = WithdrawalMethod::factory()->create(['vendor_id' => $vendor->id]);

    return compact('vendor', 'wallet', 'method');
}

beforeEach(function () {
    Setting::updateOrCreate(['key' => 'finance.minimum_withdrawal'], ['value' => '5000', 'type' => 'integer', 'group' => 'finance']);
});

test('requesting a withdrawal moves the amount from available to reserved', function () {
    ['vendor' => $vendor, 'wallet' => $wallet, 'method' => $method] = vendorWithAvailableBalance(20000);

    $withdrawal = app(RequestWithdrawalAction::class)->handle($vendor, $method, 10000);

    expect($withdrawal->status)->toBe(WithdrawalStatus::Pending)
        ->and($withdrawal->amount)->toBe(10000);

    expect($wallet->fresh())
        ->available_balance->toBe(10000)
        ->reserved_balance->toBe(10000);
});

test('a withdrawal below the minimum is rejected', function () {
    ['vendor' => $vendor, 'method' => $method] = vendorWithAvailableBalance(20000);

    expect(fn () => app(RequestWithdrawalAction::class)->handle($vendor, $method, 1000))
        ->toThrow(InsufficientWalletBalanceException::class);
});

test('a withdrawal method belonging to another vendor cannot be used', function () {
    ['vendor' => $vendor] = vendorWithAvailableBalance(20000);
    $otherMethod = WithdrawalMethod::factory()->create();

    expect(fn () => app(RequestWithdrawalAction::class)->handle($vendor, $otherMethod, 10000))
        ->toThrow(InsufficientWalletBalanceException::class);
});

test('over-withdrawal is impossible: a second request exceeding the remaining available balance is rejected', function () {
    ['vendor' => $vendor, 'wallet' => $wallet, 'method' => $method] = vendorWithAvailableBalance(10000);

    app(RequestWithdrawalAction::class)->handle($vendor, $method, 10000);

    expect($wallet->fresh()->available_balance)->toBe(0);

    expect(fn () => app(RequestWithdrawalAction::class)->handle($vendor, $method, 5000))
        ->toThrow(InsufficientWalletBalanceException::class);

    expect($wallet->fresh())
        ->available_balance->toBe(0)
        ->reserved_balance->toBe(10000);
});

test('an admin can approve a pending withdrawal without moving any funds', function () {
    ['vendor' => $vendor, 'wallet' => $wallet, 'method' => $method] = vendorWithAvailableBalance(20000);
    $withdrawal = app(RequestWithdrawalAction::class)->handle($vendor, $method, 10000);
    $admin = User::factory()->admin()->create();

    $approved = app(ApproveWithdrawalAction::class)->handle($withdrawal, $admin);

    expect($approved->status)->toBe(WithdrawalStatus::Approved)
        ->and($approved->reviewed_by)->toBe($admin->id);

    expect($wallet->fresh())
        ->reserved_balance->toBe(10000)
        ->withdrawn_balance->toBe(0);
});

test('an approved withdrawal cannot be approved again', function () {
    ['vendor' => $vendor, 'method' => $method] = vendorWithAvailableBalance(20000);
    $withdrawal = app(RequestWithdrawalAction::class)->handle($vendor, $method, 10000);
    $admin = User::factory()->admin()->create();
    app(ApproveWithdrawalAction::class)->handle($withdrawal, $admin);

    expect(fn () => app(ApproveWithdrawalAction::class)->handle($withdrawal->fresh(), $admin))
        ->toThrow(InvalidWithdrawalTransitionException::class);
});

test('rejecting a pending withdrawal releases the hold back to available balance', function () {
    ['vendor' => $vendor, 'wallet' => $wallet, 'method' => $method] = vendorWithAvailableBalance(20000);
    $withdrawal = app(RequestWithdrawalAction::class)->handle($vendor, $method, 10000);
    $admin = User::factory()->admin()->create();

    $rejected = app(RejectWithdrawalAction::class)->handle($withdrawal, 'Bad bank details', $admin);

    expect($rejected->status)->toBe(WithdrawalStatus::Rejected)
        ->and($rejected->rejection_reason)->toBe('Bad bank details');

    expect($wallet->fresh())
        ->available_balance->toBe(20000)
        ->reserved_balance->toBe(0);
});

test('an approved withdrawal can still be rejected', function () {
    ['vendor' => $vendor, 'wallet' => $wallet, 'method' => $method] = vendorWithAvailableBalance(20000);
    $withdrawal = app(RequestWithdrawalAction::class)->handle($vendor, $method, 10000);
    $admin = User::factory()->admin()->create();
    $approved = app(ApproveWithdrawalAction::class)->handle($withdrawal, $admin);

    app(RejectWithdrawalAction::class)->handle($approved, 'Compliance hold', $admin);

    expect($wallet->fresh())->available_balance->toBe(20000);
});

test('marking an approved withdrawal paid moves reserved funds to withdrawn', function () {
    ['vendor' => $vendor, 'wallet' => $wallet, 'method' => $method] = vendorWithAvailableBalance(20000);
    $withdrawal = app(RequestWithdrawalAction::class)->handle($vendor, $method, 10000);
    $admin = User::factory()->admin()->create();
    $approved = app(ApproveWithdrawalAction::class)->handle($withdrawal, $admin);

    $paid = app(MarkWithdrawalPaidAction::class)->handle($approved, $admin);

    expect($paid->status)->toBe(WithdrawalStatus::Paid)
        ->and($paid->paid_at)->not->toBeNull();

    expect($wallet->fresh())
        ->reserved_balance->toBe(0)
        ->withdrawn_balance->toBe(10000);
});

test('a pending withdrawal cannot be marked paid without first being approved', function () {
    ['vendor' => $vendor, 'method' => $method] = vendorWithAvailableBalance(20000);
    $withdrawal = app(RequestWithdrawalAction::class)->handle($vendor, $method, 10000);
    $admin = User::factory()->admin()->create();

    expect(fn () => app(MarkWithdrawalPaidAction::class)->handle($withdrawal, $admin))
        ->toThrow(InvalidWithdrawalTransitionException::class);
});

test('a vendor can cancel their own pending withdrawal, releasing the hold', function () {
    ['vendor' => $vendor, 'wallet' => $wallet, 'method' => $method] = vendorWithAvailableBalance(20000);
    $withdrawal = app(RequestWithdrawalAction::class)->handle($vendor, $method, 10000);

    $cancelled = app(CancelWithdrawalAction::class)->handle($withdrawal);

    expect($cancelled->status)->toBe(WithdrawalStatus::Cancelled);
    expect($wallet->fresh())
        ->available_balance->toBe(20000)
        ->reserved_balance->toBe(0);
});

test('an approved withdrawal can no longer be cancelled by the vendor', function () {
    ['vendor' => $vendor, 'method' => $method] = vendorWithAvailableBalance(20000);
    $withdrawal = app(RequestWithdrawalAction::class)->handle($vendor, $method, 10000);
    $admin = User::factory()->admin()->create();
    $approved = app(ApproveWithdrawalAction::class)->handle($withdrawal, $admin);

    expect(fn () => app(CancelWithdrawalAction::class)->handle($approved))
        ->toThrow(InvalidWithdrawalTransitionException::class);
});
