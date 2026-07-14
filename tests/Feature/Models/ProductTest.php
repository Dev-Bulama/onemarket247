<?php

use App\Enums\StockStatus;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductDigitalFile;
use App\Models\ProductTranslation;
use App\Models\ProductVariation;
use Illuminate\Support\Facades\Storage;

test('translated name returns the translation for the requested language when one exists', function () {
    $product = Product::factory()->create(['name' => 'Widget']);
    $french = Language::factory()->create(['code' => 'fr']);
    ProductTranslation::factory()->for($product)->for($french, 'language')->create(['name' => 'Gadget']);

    expect($product->translatedName('fr'))->toBe('Gadget')
        ->and($product->translatedName('en'))->toBe('Widget');
});

test('translated name falls back to the base name when no translation exists for that language', function () {
    $product = Product::factory()->create(['name' => 'Widget']);
    $german = Language::factory()->create(['code' => 'de']);

    expect($product->translatedName('de'))->toBe('Widget');
});

test('translated short description and description fall back to the base columns', function () {
    $product = Product::factory()->create(['short_description' => 'Short', 'description' => 'Long']);
    $french = Language::factory()->create(['code' => 'fr']);
    ProductTranslation::factory()->for($product)->for($french, 'language')->create([
        'short_description' => 'Court',
        'description' => null,
    ]);

    expect($product->translatedShortDescription('fr'))->toBe('Court')
        ->and($product->translatedDescription('fr'))->toBe('Long');
});

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

test('displayPrice returns a simple products own price', function () {
    $product = Product::factory()->create(['price' => 1999]);

    expect($product->displayPrice())->toBe(1999)
        ->and($product->displayPriceRange())->toBeNull();
});

test('displayPrice returns the lowest active variation price for a variable product', function () {
    $product = Product::factory()->variable()->create();
    ProductVariation::factory()->create(['product_id' => $product->id, 'price' => 900, 'is_active' => true]);
    ProductVariation::factory()->create(['product_id' => $product->id, 'price' => 500, 'is_active' => true]);
    ProductVariation::factory()->create(['product_id' => $product->id, 'price' => 100, 'is_active' => false]);

    expect($product->displayPrice())->toBe(500)
        ->and($product->displayPriceRange())->toBe(['min' => 500, 'max' => 900]);
});

test('a published product is visible to customers and every other status is not', function () {
    $published = Product::factory()->create();
    $draft = Product::factory()->draft()->create();

    expect($published->isVisibleToCustomers())->toBeTrue()
        ->and($draft->isVisibleToCustomers())->toBeFalse();
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
