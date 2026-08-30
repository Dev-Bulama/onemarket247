<?php

use App\Enums\StoreStaffStatus;
use App\Enums\UserType;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductTag;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
    Storage::fake('public');
});

test('a vendor can create a product with images, categories and tags', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $category = Category::factory()->create();
    $tag = ProductTag::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/vendor/products', [
            'name' => 'A Brand New Product',
            'price' => 1500,
            'stock_status' => 'in_stock',
            'categories' => [$category->id],
            'tags' => [$tag->id],
            'images' => [UploadedFile::fake()->image('cover.jpg')],
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'A Brand New Product')
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.price.amount', 1500);

    $product = Product::where('name', 'A Brand New Product')->firstOrFail();
    expect($product->vendor_id)->toBe($vendor->id)
        ->and($product->categories->pluck('id')->all())->toBe([$category->id])
        ->and($product->tags->pluck('id')->all())->toBe([$tag->id])
        ->and($product->getMedia('images'))->toHaveCount(1);
});

test('an omitted slug is auto-derived and de-duplicated', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    Product::factory()->create(['slug' => 'cool-widget']);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/vendor/products', [
            'name' => 'Cool Widget',
            'price' => 500,
            'stock_status' => 'in_stock',
        ]);

    $response->assertCreated();
    expect($response->json('data.slug'))->toBe('cool-widget-1');
});

test('creating a product without a name is rejected', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/vendor/products', ['price' => 500, 'stock_status' => 'in_stock'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('name');
});

test('a store staff member without store.products.manage cannot create a product', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->create(['vendor_id' => $vendor->id]);

    $staffUser = User::factory()->create(['user_type' => UserType::VendorStaff]);
    StoreStaff::factory()->create(['store_id' => $store->id, 'user_id' => $staffUser->id, 'status' => StoreStaffStatus::Active]);
    $token = $staffUser->createToken('t', ['vendor:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/vendor/products', ['name' => 'Nope', 'price' => 500, 'stock_status' => 'in_stock'])
        ->assertForbidden();
});

test('a store staff member with store.products.manage can create a product', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->create(['vendor_id' => $vendor->id]);

    $staffUser = User::factory()->create(['user_type' => UserType::VendorStaff]);
    StoreStaff::factory()->create(['store_id' => $store->id, 'user_id' => $staffUser->id, 'status' => StoreStaffStatus::Active]);
    $staffUser->givePermissionTo(Permission::where('name', 'store.products.manage')->where('guard_name', 'vendor')->first());
    $token = $staffUser->createToken('t', ['vendor:*'])->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/vendor/products', ['name' => 'Staff Made Product', 'price' => 500, 'stock_status' => 'in_stock'])
        ->assertCreated();

    expect(Product::where('name', 'Staff Made Product')->first()?->vendor_id)->toBe($vendor->id);
});

test('a vendor can delete their own product', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/vendor/products/{$product->id}")
        ->assertOk();

    expect(Product::withTrashed()->find($product->id)->trashed())->toBeTrue();
});

test('a vendor cannot delete another vendor\'s product', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $token = $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;

    $otherVendor = Vendor::factory()->create();
    $otherProduct = Product::factory()->create(['vendor_id' => $otherVendor->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/vendor/products/{$otherProduct->id}")
        ->assertForbidden();

    expect(Product::find($otherProduct->id))->not->toBeNull();
});
