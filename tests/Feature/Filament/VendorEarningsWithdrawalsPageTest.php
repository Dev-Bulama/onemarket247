<?php

use App\Actions\Wallet\CreditVendorWalletAction;
use App\Filament\Vendor\Pages\Withdrawals;
use App\Models\OrderItem;
use App\Models\OrderItemCommission;
use App\Models\Store;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorWallet;
use App\Models\Withdrawal;
use App\Models\WithdrawalMethod;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('a vendor can load the earnings and withdrawals pages', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    VendorWallet::factory()->create(['vendor_id' => $vendor->id, 'available_balance' => 20000]);

    $this->actingAs($vendor->user, 'vendor')->get('/vendor/earnings')->assertOk();
    $this->actingAs($vendor->user, 'vendor')->get('/vendor/withdrawals')->assertOk();
});

test('the earnings page ledger table only shows this vendor\'s own wallet transactions', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();

    $vendorOrder = VendorOrder::factory()->create(['vendor_id' => $vendor->id]);
    $item = OrderItem::factory()->create(['vendor_order_id' => $vendorOrder->id, 'line_total' => 10000]);
    OrderItemCommission::factory()->create(['order_item_id' => $item->id, 'gross_amount' => 10000, 'commission_amount' => 1000, 'net_amount' => 9000]);
    app(CreditVendorWalletAction::class)->handle($vendorOrder);

    $otherVendor = Vendor::factory()->create();
    $otherVendorOrder = VendorOrder::factory()->create(['vendor_id' => $otherVendor->id]);
    $otherItem = OrderItem::factory()->create(['vendor_order_id' => $otherVendorOrder->id, 'line_total' => 5000]);
    OrderItemCommission::factory()->create(['order_item_id' => $otherItem->id, 'gross_amount' => 5000, 'commission_amount' => 500, 'net_amount' => 4500]);
    app(CreditVendorWalletAction::class)->handle($otherVendorOrder);

    $this->actingAs($vendor->user, 'vendor')
        ->get('/vendor/earnings')
        ->assertOk()
        ->assertSee('$90.00')
        ->assertDontSee('$45.00');
});

test('a vendor can add a bank account and request a withdrawal from the withdrawals page', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    VendorWallet::factory()->create(['vendor_id' => $vendor->id, 'available_balance' => 20000]);

    Filament::setCurrentPanel('vendor');

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(Withdrawals::class)
        ->callAction('addMethod', data: [
            'bank_name' => 'Test Bank',
            'account_name' => 'Test Vendor',
            'account_number' => '0000111122',
            'is_default' => true,
        ]);

    $method = WithdrawalMethod::where('vendor_id', $vendor->id)->firstOrFail();
    expect($method->bank_name)->toBe('Test Bank');

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(Withdrawals::class)
        ->callAction('request', data: [
            'withdrawal_method_id' => $method->id,
            'amount' => 100,
        ]);

    expect($vendor->wallet->fresh())
        ->available_balance->toBe(10000)
        ->reserved_balance->toBe(10000);

    $withdrawal = Withdrawal::where('vendor_id', $vendor->id)->firstOrFail();
    expect($withdrawal->amount)->toBe(10000);
});

test('a vendor cannot request a withdrawal that exceeds their available balance', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    VendorWallet::factory()->create(['vendor_id' => $vendor->id, 'available_balance' => 5000]);
    $method = WithdrawalMethod::factory()->create(['vendor_id' => $vendor->id]);

    Filament::setCurrentPanel('vendor');

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(Withdrawals::class)
        ->callAction('request', data: [
            'withdrawal_method_id' => $method->id,
            'amount' => 100,
        ]);

    expect(Withdrawal::where('vendor_id', $vendor->id)->count())->toBe(0);
    expect($vendor->wallet->fresh()->available_balance)->toBe(5000);
});

test('a vendor can cancel their own pending withdrawal from the withdrawals page', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $wallet = VendorWallet::factory()->create(['vendor_id' => $vendor->id, 'available_balance' => 0, 'reserved_balance' => 10000]);
    $method = WithdrawalMethod::factory()->create(['vendor_id' => $vendor->id]);
    $withdrawal = Withdrawal::factory()->create(['vendor_id' => $vendor->id, 'withdrawal_method_id' => $method->id, 'amount' => 10000]);

    Filament::setCurrentPanel('vendor');

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(Withdrawals::class)
        ->callTableAction('cancel', $withdrawal);

    expect($withdrawal->fresh()->status->value)->toBe('cancelled');
    expect($wallet->fresh())
        ->available_balance->toBe(10000)
        ->reserved_balance->toBe(0);
});
