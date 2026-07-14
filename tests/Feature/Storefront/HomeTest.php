<?php

use App\Actions\Product\BestSellingProductsAction;
use App\Enums\VendorOrderStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\VendorOrder;

test('the homepage loads with real featured content', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['is_featured' => true]);
    $store = Store::factory()->create(['is_featured' => true]);

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee($category->name)
        ->assertSee($product->name)
        ->assertSee($store->name);
});

test('the homepage does not error with no data at all', function () {
    $this->get('/')->assertOk();
});

test('the homepage new arrivals row includes any published product, not just featured ones', function () {
    $featured = Product::factory()->create(['is_featured' => true, 'name' => 'Featured Widget']);
    $notFeatured = Product::factory()->create(['is_featured' => false, 'name' => 'Ordinary Widget']);

    $this->get('/')->assertOk()->assertSee($featured->name)->assertSee($notFeatured->name);
});

test('the homepage never shows a draft product', function () {
    $draft = Product::factory()->draft()->create(['name' => 'Secret Draft Widget']);

    $this->get('/')->assertOk()->assertDontSee($draft->name);
});

test('the homepage shows active brands but not inactive ones', function () {
    $active = Brand::factory()->create(['is_active' => true, 'name' => 'Active Brand']);
    $inactive = Brand::factory()->create(['is_active' => false, 'name' => 'Inactive Brand']);

    $this->get('/')->assertOk()->assertSee($active->name)->assertDontSee($inactive->name);
});

test('best sellers reflect real units sold, ignoring pending and cancelled orders', function () {
    $soldProduct = Product::factory()->create(['name' => 'Actually Sold Widget']);
    $confirmedOrder = VendorOrder::factory()->create(['status' => VendorOrderStatus::Confirmed]);
    OrderItem::factory()->create(['vendor_order_id' => $confirmedOrder->id, 'product_id' => $soldProduct->id, 'quantity' => 5]);

    $pendingProduct = Product::factory()->create(['name' => 'Only Pending Widget']);
    $pendingOrder = VendorOrder::factory()->create(['status' => VendorOrderStatus::PendingPayment]);
    OrderItem::factory()->create(['vendor_order_id' => $pendingOrder->id, 'product_id' => $pendingProduct->id, 'quantity' => 10]);

    $cancelledProduct = Product::factory()->create(['name' => 'Only Cancelled Widget']);
    $cancelledOrder = VendorOrder::factory()->create(['status' => VendorOrderStatus::Cancelled]);
    OrderItem::factory()->create(['vendor_order_id' => $cancelledOrder->id, 'product_id' => $cancelledProduct->id, 'quantity' => 10]);

    $bestSellers = app(BestSellingProductsAction::class)->handle(12);

    expect($bestSellers->pluck('id')->all())->toBe([$soldProduct->id]);
});

test('best sellers with a real sale still render on the homepage', function () {
    $soldProduct = Product::factory()->create(['name' => 'Actually Sold Widget']);
    $confirmedOrder = VendorOrder::factory()->create(['status' => VendorOrderStatus::Confirmed]);
    OrderItem::factory()->create(['vendor_order_id' => $confirmedOrder->id, 'product_id' => $soldProduct->id, 'quantity' => 5]);

    $this->get('/')->assertOk()->assertSeeInOrder(['Best sellers', $soldProduct->name]);
});

test('trending products are ordered by view count', function () {
    $popular = Product::factory()->create(['name' => 'Popular Widget', 'view_count' => 500]);
    $obscure = Product::factory()->create(['name' => 'Obscure Widget', 'view_count' => 1]);

    $this->get('/')->assertOk()->assertSeeInOrder([$popular->name, $obscure->name]);
});
