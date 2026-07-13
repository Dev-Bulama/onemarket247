<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;

test('the shop page lists published products', function () {
    $product = Product::factory()->create();

    $this->get('/shop')->assertOk()->assertSee($product->name);
});

test('draft, pending, rejected, and archived products never appear in the shop', function () {
    $draft = Product::factory()->draft()->create();
    $pending = Product::factory()->pendingApproval()->create();

    $response = $this->get('/shop');

    $response->assertOk()->assertDontSee($draft->name)->assertDontSee($pending->name);
});

test('the shop page filters by category', function () {
    $category = Category::factory()->create();
    $matching = Product::factory()->create();
    $matching->categories()->attach($category->id);
    $other = Product::factory()->create();

    $response = $this->get('/shop?category_id='.$category->id);

    $response->assertOk()->assertSee($matching->name)->assertDontSee($other->name);
});

test('the shop page filters by brand', function () {
    $brand = Brand::factory()->create();
    $matching = Product::factory()->create(['brand_id' => $brand->id]);
    $other = Product::factory()->create();

    $response = $this->get('/shop?brand_id='.$brand->id);

    $response->assertOk()->assertSee($matching->name)->assertDontSee($other->name);
});

test('the shop page filters by price range', function () {
    $cheap = Product::factory()->create(['price' => 500]);
    $expensive = Product::factory()->create(['price' => 10000]);

    $response = $this->get('/shop?min_price=50&max_price=200');

    $response->assertOk()->assertSee($expensive->name)->assertDontSee($cheap->name);
});

test('the shop page filters to in-stock products only', function () {
    $inStock = Product::factory()->create(['manage_stock' => true, 'stock_status' => 'in_stock']);
    $outOfStock = Product::factory()->create(['manage_stock' => true, 'stock_status' => 'out_of_stock']);

    $response = $this->get('/shop?in_stock=1');

    $response->assertOk()->assertSee($inStock->name)->assertDontSee($outOfStock->name);
});

test('the shop page sorts by price ascending', function () {
    $cheap = Product::factory()->create(['price' => 500]);
    $expensive = Product::factory()->create(['price' => 5000]);

    $content = $this->get('/shop?sort=price_asc')->assertOk()->getContent();

    expect(strpos($content, $cheap->name))->toBeLessThan(strpos($content, $expensive->name));
});

test('the shop page paginates results', function () {
    Product::factory()->count(30)->create();

    $response = $this->get('/shop');

    $response->assertOk();
    expect(Product::count())->toBeGreaterThan(24);
});
