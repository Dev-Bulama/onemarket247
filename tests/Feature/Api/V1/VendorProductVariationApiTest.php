<?php

use App\Enums\StockStatus;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Store;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

function variationVendorToken(Vendor $vendor): string
{
    return $vendor->user->createToken('t', ['vendor:*'])->plainTextToken;
}

test('a vendor can list attributes eligible for variations', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $token = variationVendorToken($vendor);

    $attribute = Attribute::factory()->create(['name' => 'Colour', 'is_variation' => true]);
    AttributeValue::factory()->for($attribute)->create(['value' => 'Blue']);
    Attribute::factory()->create(['is_variation' => false]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/vendor/attributes');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1)
        ->and($response->json('data.0.name'))->toBe('Colour')
        ->and($response->json('data.0.values.0.value'))->toBe('Blue');
});

test('a vendor can create a variation for their own variable product, with real stock seeded', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $warehouse = Warehouse::create(['vendor_id' => $vendor->id, 'name' => 'Main', 'code' => 'MAIN', 'is_default' => true]);
    $product = Product::factory()->for($vendor)->variable()->create();
    $attribute = Attribute::factory()->create(['is_variation' => true]);
    $value = AttributeValue::factory()->for($attribute)->create();
    $token = variationVendorToken($vendor);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/vendor/products/{$product->id}/variations", [
            'sku' => 'VAR-1',
            'price' => 1999,
            'stock_quantity' => 5,
            'attribute_value_ids' => [$value->id],
        ]);

    $response->assertCreated()
        ->assertJsonPath('data.sku', 'VAR-1')
        ->assertJsonPath('data.price.amount', 1999)
        ->assertJsonPath('data.attributes.0.value', $value->value);

    $variation = ProductVariation::where('sku', 'VAR-1')->firstOrFail();
    expect($variation->stock_status)->toBe(StockStatus::InStock);

    $stock = $variation->warehouseStocks()->first();
    expect($stock)->not->toBeNull()
        ->and($stock->warehouse_id)->toBe($warehouse->id)
        ->and($stock->on_hand)->toBe(5);
});

test('a vendor cannot create a variation for another vendor\'s product', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $token = variationVendorToken($vendor);

    $otherVendor = Vendor::factory()->create();
    $otherProduct = Product::factory()->for($otherVendor)->variable()->create();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/vendor/products/{$otherProduct->id}/variations", [
            'sku' => 'VAR-1',
            'price' => 1999,
        ]);

    $response->assertForbidden();
});

test('a vendor can update a variation\'s price and attributes', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->for($vendor)->variable()->create();
    $variation = ProductVariation::factory()->create(['product_id' => $product->id, 'price' => 1000]);
    $attribute = Attribute::factory()->create(['is_variation' => true]);
    $value = AttributeValue::factory()->for($attribute)->create();
    $token = variationVendorToken($vendor);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson("/api/v1/vendor/products/{$product->id}/variations/{$variation->id}", [
            'sku' => $variation->sku,
            'price' => 2500,
            'is_active' => true,
            'attribute_value_ids' => [$value->id],
        ]);

    $response->assertOk()->assertJsonPath('data.price.amount', 2500);

    expect($variation->fresh()->price)->toBe(2500)
        ->and($variation->fresh()->attributeValues()->pluck('attribute_values.id')->all())->toBe([$value->id]);
});

test('a vendor can attach a photo when creating a variation', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->for($vendor)->variable()->create();
    $token = variationVendorToken($vendor);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/vendor/products/{$product->id}/variations", [
            'sku' => 'VAR-PHOTO',
            'price' => 1999,
            'image' => UploadedFile::fake()->image('swatch.jpg'),
        ]);

    $response->assertCreated()->assertJsonPath('data.image', fn ($url) => filled($url));
});

test('a vendor can delete their own variation', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $vendor->id]);
    $product = Product::factory()->for($vendor)->variable()->create();
    $variation = ProductVariation::factory()->create(['product_id' => $product->id]);
    $token = variationVendorToken($vendor);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/vendor/products/{$product->id}/variations/{$variation->id}");

    $response->assertOk();
    expect(ProductVariation::find($variation->id))->toBeNull();
});
