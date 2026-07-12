<?php

use App\Enums\ProductStatus;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Attribute;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
});

function superAdminForCatalog(): User
{
    $user = User::factory()->admin()->create();
    $user->assignRole(Role::where('name', 'Super Admin')->where('guard_name', 'admin')->first());

    return $user;
}

test('a super admin can load all catalog pages', function () {
    $admin = superAdminForCatalog();
    $product = Product::factory()->create();
    $attribute = Attribute::factory()->create();

    $this->actingAs($admin, 'admin')->get('/admin/categories')->assertOk();
    $this->actingAs($admin, 'admin')->get('/admin/brands')->assertOk();
    $this->actingAs($admin, 'admin')->get('/admin/attributes')->assertOk();
    $this->actingAs($admin, 'admin')->get("/admin/attributes/{$attribute->id}/edit")->assertOk();
    $this->actingAs($admin, 'admin')->get('/admin/collections')->assertOk();
    $this->actingAs($admin, 'admin')->get('/admin/products')->assertOk();
    $this->actingAs($admin, 'admin')->get("/admin/products/{$product->id}/edit")->assertOk();
});

test('an admin can approve a pending product from the list', function () {
    $admin = superAdminForCatalog();
    $product = Product::factory()->pendingApproval()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ListProducts::class)
        ->callTableAction('approve', $product);

    expect($product->fresh()->status)->toBe(ProductStatus::Published);
});

test('an admin can reject a pending product with a reason', function () {
    $admin = superAdminForCatalog();
    $product = Product::factory()->pendingApproval()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ListProducts::class)
        ->callTableAction('reject', $product, data: ['reason' => 'Blurry photos']);

    $product->refresh();
    expect($product->status)->toBe(ProductStatus::Rejected)
        ->and($product->rejection_reason)->toBe('Blurry photos');
});

test('approve/reject actions are hidden for a product that is not pending', function () {
    $admin = superAdminForCatalog();
    $product = Product::factory()->create();

    Livewire::actingAs($admin, 'admin')
        ->test(ListProducts::class)
        ->assertTableActionHidden('approve', $product)
        ->assertTableActionHidden('reject', $product);
});

test('a super admin can toggle a products featured flag', function () {
    $admin = superAdminForCatalog();
    $product = Product::factory()->create(['is_featured' => false]);

    Livewire::actingAs($admin, 'admin')
        ->test(ListProducts::class)
        ->callTableAction('toggleFeatured', $product);

    expect($product->fresh()->is_featured)->toBeTrue();
});

test('catalog staff without products.feature cannot see the feature action', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Catalog Staff')->where('guard_name', 'admin')->first());
    $product = Product::factory()->create();

    Livewire::actingAs($staff, 'admin')
        ->test(ListProducts::class)
        ->assertTableActionHidden('toggleFeatured', $product);
});

test('a support staff admin without catalog permissions cannot access categories', function () {
    $staff = User::factory()->admin()->create();
    $staff->assignRole(Role::where('name', 'Support Staff')->where('guard_name', 'admin')->first());

    $this->actingAs($staff, 'admin')->get('/admin/categories')->assertForbidden();
});
