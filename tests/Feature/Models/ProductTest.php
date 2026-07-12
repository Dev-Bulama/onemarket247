<?php

use App\Enums\StockStatus;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductDigitalFile;
use App\Models\ProductVariation;
use Illuminate\Support\Facades\Storage;

test('primary category falls back to the first attached category when none is marked primary', function () {
    $product = Product::factory()->create();
    $categoryA = Category::factory()->create();
    $categoryB = Category::factory()->create();
    $product->categories()->attach([$categoryA->id, $categoryB->id]);

    expect($product->primaryCategory()->id)->toBe($categoryA->id);
});

test('primary category prefers the category explicitly marked primary', function () {
    $product = Product::factory()->create();
    $categoryA = Category::factory()->create();
    $categoryB = Category::factory()->create();
    $product->categories()->attach([
        $categoryA->id => ['is_primary' => false],
        $categoryB->id => ['is_primary' => true],
    ]);

    expect($product->primaryCategory()->id)->toBe($categoryB->id);
});

test('a product that does not manage stock is always in stock', function () {
    $product = Product::factory()->create(['manage_stock' => false, 'stock_status' => StockStatus::OutOfStock]);

    expect($product->isInStock())->toBeTrue();
});

test('a stock-managed product reflects its stock_status', function () {
    $inStock = Product::factory()->create(['manage_stock' => true, 'stock_status' => StockStatus::InStock]);
    $outOfStock = Product::factory()->create(['manage_stock' => true, 'stock_status' => StockStatus::OutOfStock]);

    expect($inStock->isInStock())->toBeTrue()
        ->and($outOfStock->isInStock())->toBeFalse();
});

test('a variation follows the same stock rules as a product', function () {
    $variation = ProductVariation::factory()->create(['manage_stock' => true, 'stock_status' => StockStatus::OnBackorder]);
    $unmanaged = ProductVariation::factory()->create(['manage_stock' => false, 'stock_status' => StockStatus::OutOfStock]);

    expect($variation->isInStock())->toBeFalse()
        ->and($unmanaged->isInStock())->toBeTrue();
});

test('a variation is attached to the attribute values that define it', function () {
    $variation = ProductVariation::factory()->create();
    $color = AttributeValue::factory()->create(['value' => 'Red', 'color_code' => '#FF0000']);

    $variation->attributeValues()->attach($color->id);

    expect($variation->attributeValues()->first()->color_code)->toBe('#FF0000');
});

test('deleting a digital file record removes the underlying file from disk', function () {
    Storage::fake('local');
    Storage::disk('local')->put('product-digital-files/1/secret.pdf', 'contents');

    $file = ProductDigitalFile::factory()->create([
        'file_path' => 'product-digital-files/1/secret.pdf',
    ]);

    Storage::disk('local')->assertExists($file->file_path);

    $file->delete();

    Storage::disk('local')->assertMissing($file->file_path);
});
