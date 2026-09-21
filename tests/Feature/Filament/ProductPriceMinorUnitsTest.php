<?php

use App\Enums\ProductType;
use App\Filament\Resources\Products\Pages\EditProduct as AdminEditProduct;
use App\Filament\Resources\Products\Pages\ListProducts as AdminListProducts;
use App\Filament\Resources\ShippingZones\Pages\EditShippingZone;
use App\Filament\Resources\ShippingZones\RelationManagers\RatesRelationManager;
use App\Filament\Resources\Stores\Pages\EditStore;
use App\Filament\Resources\VendorSubscriptionPlans\Pages\EditVendorSubscriptionPlan;
use App\Filament\Resources\VendorSubscriptionPlans\Pages\ListVendorSubscriptionPlans;
use App\Filament\Vendor\Resources\Products\Pages\CreateProduct;
use App\Filament\Vendor\Resources\Products\Pages\EditProduct as VendorEditProduct;
use App\Filament\Vendor\Resources\Products\Pages\ListProducts as VendorListProducts;
use App\Filament\Vendor\Resources\Products\RelationManagers\VariationsRelationManager;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\ShippingZone;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorSubscriptionPlan;
use App\Models\Warehouse;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

/**
 * Regression coverage for a real bug: every editable *_price/amount field
 * (unsignedBigInteger columns storing minor units) was a plain numeric
 * TextInput with only a small "minor units" helper text as a warning — a
 * vendor/admin typing "29.99" as any human would got it stored (and
 * later displayed via ->money()) as if it meant $29.00 or $2,999.00
 * depending on which layer you looked at. See App\Support\Filament\MinorUnitsInput.
 */
function priceSyncAdmin(): User
{
    $admin = User::factory()->admin()->create();
    $admin->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $admin;
}

test('a vendor typing a normal price on the create form gets the correct minor-unit value stored', function () {
    Storage::fake('public');
    Filament::setCurrentPanel('vendor');
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    Warehouse::create(['vendor_id' => $vendor->id, 'name' => 'Main', 'code' => 'MAIN', 'is_default' => true]);

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(CreateProduct::class)
        ->fillForm([
            'name' => 'Price Test Widget',
            'slug' => 'price-test-widget',
            'sku' => 'PTW-1',
            'type' => ProductType::Simple->value,
            'price' => 29.99,
            'compare_at_price' => 39.99,
            'stock_quantity' => 5,
            'stock_status' => 'in_stock',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::where('slug', 'price-test-widget')->firstOrFail();
    expect($product->price)->toBe(2999)
        ->and($product->compare_at_price)->toBe(3999);
});

test('editing a product on the vendor form shows the price in major units and round-trips correctly', function () {
    Filament::setCurrentPanel('vendor');
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->for($vendor)->create(['price' => 2999, 'compare_at_price' => 3999]);

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(VendorEditProduct::class, ['record' => $product->getRouteKey()])
        ->assertFormSet(['price' => 29.99, 'compare_at_price' => 39.99])
        ->fillForm(['price' => 34.99])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->fresh()->price)->toBe(3499);
});

test('the vendor product list shows the real price, not a 100x-inflated one', function () {
    Filament::setCurrentPanel('vendor');
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    Product::factory()->for($vendor)->create(['name' => 'List Price Widget', 'price' => 2999]);

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(VendorListProducts::class)
        ->assertSee('29.99')
        ->assertDontSee('2,999.00');
});

test('an admin typing a normal price on the product form gets the correct minor-unit value stored', function () {
    Filament::setCurrentPanel('admin');
    $admin = priceSyncAdmin();
    $product = Product::factory()->create(['price' => 1000]);

    Livewire::actingAs($admin, 'admin')
        ->test(AdminEditProduct::class, ['record' => $product->getRouteKey()])
        ->assertFormSet(['price' => 10.00])
        ->fillForm(['price' => 45.50])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($product->fresh()->price)->toBe(4550);
});

test('the admin product list shows the real price, not a 100x-inflated one', function () {
    Filament::setCurrentPanel('admin');
    $admin = priceSyncAdmin();
    Product::factory()->create(['name' => 'Admin List Widget', 'price' => 2999]);

    Livewire::actingAs($admin, 'admin')
        ->test(AdminListProducts::class)
        ->assertSee('29.99')
        ->assertDontSee('2,999.00');
});

test('a variation price round-trips correctly through the vendor relation manager', function () {
    Filament::setCurrentPanel('vendor');
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->for($vendor)->variable()->create();
    $variation = ProductVariation::factory()->create(['product_id' => $product->id, 'price' => 999]);

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(VariationsRelationManager::class, [
            'ownerRecord' => $product,
            'pageClass' => VendorEditProduct::class,
        ])
        ->assertSee('9.99')
        ->assertDontSee('999.00')
        ->callTableAction('edit', $variation, data: [
            'sku' => $variation->sku,
            'price' => 14.99,
            'stock_quantity' => 3,
        ]);

    expect($variation->fresh()->price)->toBe(1499);
});

test('a vendor subscription plan price round-trips correctly on the admin form', function () {
    Filament::setCurrentPanel('admin');
    $admin = priceSyncAdmin();
    $plan = VendorSubscriptionPlan::factory()->create(['price' => 2900]);

    Livewire::actingAs($admin, 'admin')
        ->test(EditVendorSubscriptionPlan::class, ['record' => $plan->getRouteKey()])
        ->assertFormSet(['price' => 29.00])
        ->fillForm(['price' => 19.00])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($plan->fresh()->price)->toBe(1900);
});

test('the vendor subscription plans list shows the real price', function () {
    Filament::setCurrentPanel('admin');
    $admin = priceSyncAdmin();
    VendorSubscriptionPlan::factory()->create(['name' => 'Pro Plan', 'price' => 2900]);

    Livewire::actingAs($admin, 'admin')
        ->test(ListVendorSubscriptionPlans::class)
        ->assertSee('29.00')
        ->assertDontSee('2,900.00');
});

test('a shipping rate base amount round-trips correctly through the relation manager', function () {
    Filament::setCurrentPanel('admin');
    $admin = priceSyncAdmin();
    $zone = ShippingZone::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(RatesRelationManager::class, [
            'ownerRecord' => $zone,
            'pageClass' => EditShippingZone::class,
        ])
        ->callTableAction('create', data: [
            'name' => 'Standard',
            'rate_type' => 'flat',
            'base_amount' => 4.99,
        ]);

    expect($zone->rates()->first()->base_amount)->toBe(499);
});

test('a store minimum order amount round-trips correctly on the admin form', function () {
    Filament::setCurrentPanel('admin');
    $admin = priceSyncAdmin();
    $store = Store::factory()->create(['minimum_order_amount' => 2000]);

    Livewire::actingAs($admin, 'admin')
        ->test(EditStore::class, ['record' => $store->getRouteKey()])
        ->assertFormSet(['minimum_order_amount' => 20.00])
        ->fillForm(['minimum_order_amount' => 15.00])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($store->fresh()->minimum_order_amount)->toBe(1500);
});
