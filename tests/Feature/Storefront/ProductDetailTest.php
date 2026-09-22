<?php

use App\Models\AttributeValue;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Models\Store;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('a published product detail page shows its details', function () {
    $product = Product::factory()->create(['price' => 2999, 'short_description' => 'A great product']);

    $response = $this->get(route('products.show', $product));

    $response->assertOk()
        ->assertSee($product->name)
        ->assertSee('29.99')
        ->assertSee('A great product');
});

test('every uploaded image is rendered, with clickable thumbnails that swap the main image', function () {
    Storage::fake('public');
    $product = Product::factory()->create();
    $product->addMedia(UploadedFile::fake()->image('front.jpg'))->toMediaCollection('images');
    $product->addMedia(UploadedFile::fake()->image('back.jpg'))->toMediaCollection('images');
    $product->addMedia(UploadedFile::fake()->image('side.jpg'))->toMediaCollection('images');

    $response = $this->get(route('products.show', $product));

    $images = $product->getMedia('images');
    expect($images)->toHaveCount(3);

    $response->assertOk();

    foreach ($images as $image) {
        $response->assertSee($image->getUrl(), false);
    }

    // Thumbnails must actually be wired to swap the main image, not just
    // rendered as static decoration — the storefront bug being fixed here
    // was thumbnails for every image beyond the first doing nothing at all.
    $response->assertSee('product-gallery-main-image', false);
    $response->assertSee('data-full-url', false);
});

test('a single-image product shows no thumbnail row', function () {
    Storage::fake('public');
    $product = Product::factory()->create();
    $product->addMedia(UploadedFile::fake()->image('only.jpg'))->toMediaCollection('images');

    $response = $this->get(route('products.show', $product));

    $response->assertOk()->assertDontSee('product-gallery-thumb', false);
});

test('a product with a video shows a video player', function () {
    Storage::fake('public');
    $product = Product::factory()->create();
    $product->addMedia(UploadedFile::fake()->create('walkthrough.mp4', 2048, 'video/mp4'))->toMediaCollection('videos');

    $response = $this->get(route('products.show', $product));

    $response->assertOk()
        ->assertSee('<video', false)
        ->assertSee($product->getFirstMediaUrl('videos'), false);
});

test('a product with no video shows no video player', function () {
    $product = Product::factory()->create();

    $response = $this->get(route('products.show', $product));

    $response->assertOk()->assertDontSee('<video', false);
});

test('the product page includes share links pointing at the exact product url', function () {
    $product = Product::factory()->create(['name' => 'Share Me Widget']);

    $response = $this->get(route('products.show', $product));
    $shareUrl = route('products.show', $product);

    $response->assertOk()
        ->assertSee('wa.me/?text='.urlencode('Share Me Widget '.$shareUrl), false)
        ->assertSee('facebook.com/sharer/sharer.php?u='.urlencode($shareUrl), false)
        ->assertSee('twitter.com/intent/tweet?url='.urlencode($shareUrl), false)
        ->assertSee('t.me/share/url?url='.urlencode($shareUrl), false)
        ->assertSee('mailto:?subject='.urlencode('Share Me Widget'), false)
        ->assertSee("navigator.clipboard.writeText('{$shareUrl}')", false);
});

test('a draft product 404s on the storefront', function () {
    $product = Product::factory()->draft()->create();

    $this->get(route('products.show', $product))->assertNotFound();
});

test('a pending approval product 404s on the storefront', function () {
    $product = Product::factory()->pendingApproval()->create();

    $this->get(route('products.show', $product))->assertNotFound();
});

test('a variable products active variations are listed with their own prices', function () {
    $product = Product::factory()->variable()->create();
    $variation = ProductVariation::factory()->create(['product_id' => $product->id, 'price' => 1500, 'is_active' => true]);
    $value = AttributeValue::factory()->create(['value' => 'Large']);
    $variation->attributeValues()->attach($value->id);

    $response = $this->get(route('products.show', $product));

    $response->assertOk()->assertSee('Large')->assertSee('15.00');
});

test('an inactive variation is not shown on the product page', function () {
    $product = Product::factory()->variable()->create();
    $inactive = ProductVariation::factory()->create(['product_id' => $product->id, 'sku' => 'INACTIVE-SKU', 'is_active' => false]);

    $this->get(route('products.show', $product))->assertOk()->assertDontSee($inactive->sku);
});

test('the product page links to its vendors store', function () {
    $vendor = Vendor::factory()->create();
    $store = Store::factory()->for($vendor)->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    $response = $this->get(route('products.show', $product));

    $response->assertOk()->assertSee($store->name);
});
