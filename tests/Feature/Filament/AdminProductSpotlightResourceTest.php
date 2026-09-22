<?php

use App\Enums\SpotlightDisplayArea;
use App\Filament\Resources\ProductSpotlights\Pages\CreateProductSpotlight;
use App\Filament\Resources\ProductSpotlights\Pages\EditProductSpotlight;
use App\Filament\Resources\ProductSpotlights\Pages\ListProductSpotlights;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpotlight;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function spotlightAdminUser(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('an admin can load the product spotlight list page', function () {
    $admin = spotlightAdminUser();

    $this->actingAs($admin, 'admin')->get('/admin/product-spotlights')->assertOk();
});

test('an admin without products.feature cannot access product spotlights', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Catalog Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/product-spotlights')->assertForbidden();
});

test('an admin can spotlight a product on the homepage', function () {
    $admin = spotlightAdminUser();
    $product = Product::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(CreateProductSpotlight::class)
        ->fillForm([
            'product_id' => $product->id,
            'display_area' => SpotlightDisplayArea::Homepage->value,
            'position' => 1,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $spotlight = ProductSpotlight::query()->firstOrFail();
    expect($spotlight->product_id)->toBe($product->id)
        ->and($spotlight->display_area)->toBe(SpotlightDisplayArea::Homepage);
});

test('a category spotlight requires a category', function () {
    $admin = spotlightAdminUser();
    $product = Product::factory()->create();
    $category = Category::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(CreateProductSpotlight::class)
        ->fillForm([
            'product_id' => $product->id,
            'display_area' => SpotlightDisplayArea::Category->value,
            'category_id' => $category->id,
            'position' => 0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $spotlight = ProductSpotlight::query()->firstOrFail();
    expect($spotlight->category_id)->toBe($category->id);
});

test('an admin can deactivate a spotlight without deleting it', function () {
    $spotlight = ProductSpotlight::factory()->create(['is_active' => true]);
    $admin = spotlightAdminUser();

    Livewire::actingAs($admin, 'admin')
        ->test(EditProductSpotlight::class, ['record' => $spotlight->getKey()])
        ->fillForm(['is_active' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($spotlight->fresh()->is_active)->toBeFalse();
});

test('an admin can delete a spotlight', function () {
    $spotlight = ProductSpotlight::factory()->create();
    $admin = spotlightAdminUser();

    Livewire::actingAs($admin, 'admin')
        ->test(ListProductSpotlights::class)
        ->callTableAction('delete', $spotlight);

    expect(ProductSpotlight::query()->find($spotlight->id))->toBeNull();
});
