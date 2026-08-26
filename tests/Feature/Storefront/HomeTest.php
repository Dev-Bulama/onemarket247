<?php

use App\Actions\Product\BestSellingProductsAction;
use App\Enums\VendorOrderStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\HeroSlide;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\Vendor;
use App\Models\VendorOrder;
use Illuminate\Support\Facades\Storage;

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

test('an active flash sale product renders in the flash sales rail', function () {
    $onSale = Product::factory()->create([
        'name' => 'Flash Deal Widget',
        'price' => 5000,
        'compare_at_price' => 10000,
        'flash_sale_starts_at' => now()->subHour(),
        'flash_sale_ends_at' => now()->addHours(3),
    ]);

    $this->get('/')->assertOk()->assertSeeInOrder(['Flash Sales', $onSale->name]);
});

test('an expired flash sale product does not render in the flash sales rail', function () {
    $expired = Product::factory()->create([
        'name' => 'Expired Deal Widget',
        'price' => 5000,
        'compare_at_price' => 10000,
        'flash_sale_starts_at' => now()->subHours(5),
        'flash_sale_ends_at' => now()->subHour(),
    ]);

    expect(Product::onFlashSale()->pluck('id'))->not->toContain($expired->id);
});

test('a flash sale window with no discount does not qualify', function () {
    $noDiscount = Product::factory()->create([
        'price' => 5000,
        'compare_at_price' => null,
        'flash_sale_starts_at' => now()->subHour(),
        'flash_sale_ends_at' => now()->addHours(3),
    ]);

    expect(Product::onFlashSale()->pluck('id'))->not->toContain($noDiscount->id);
});

test('recommended near you prioritizes products from the customer\'s delivery city', function () {
    $nearCity = City::factory()->create();
    $nearVendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $nearVendor->id, 'city_id' => $nearCity->id]);
    $nearProduct = Product::factory()->create(['vendor_id' => $nearVendor->id, 'name' => 'Nearby Widget']);

    $farCity = City::factory()->create();
    $farVendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $farVendor->id, 'city_id' => $farCity->id]);
    Product::factory()->create(['vendor_id' => $farVendor->id, 'name' => 'Far Away Widget']);

    $this->withSession(['delivery_location' => ['city_id' => $nearCity->id, 'state_id' => null, 'country_id' => null]])
        ->get('/')
        ->assertOk()
        ->assertSeeInOrder(['Recommended Near You', $nearProduct->name]);
});

test('the homepage hero carousel renders every active slide with dots to navigate them', function () {
    Storage::fake('public');
    $first = HeroSlide::factory()->create(['sort_order' => 0]);
    $second = HeroSlide::factory()->create(['sort_order' => 1]);
    Storage::disk('public')->put($first->image_path, 'fake-bytes-1');
    Storage::disk('public')->put($second->image_path, 'fake-bytes-2');

    $content = $this->get('/')->assertOk()->getContent();

    expect($content)
        ->toContain($first->image_path)
        ->toContain($second->image_path)
        ->toContain('hero-slide-dot')
        ->toContain('id="hero-slider"');
});

test('an inactive hero slide never appears on the homepage', function () {
    Storage::fake('public');
    $inactive = HeroSlide::factory()->inactive()->create();
    Storage::disk('public')->put($inactive->image_path, 'fake-bytes');

    $this->get('/')->assertOk()->assertDontSee($inactive->image_path, false);
});

test('a single hero slide renders without carousel dots', function () {
    Storage::fake('public');
    $only = HeroSlide::factory()->create(['sort_order' => 0]);
    Storage::disk('public')->put($only->image_path, 'fake-bytes');

    $content = $this->get('/')->assertOk()->getContent();

    expect($content)->toContain($only->image_path)
        ->not->toContain('hero-slide-dot');
});

test('the homepage falls back to a placeholder icon when there are no hero slides', function () {
    $this->get('/')->assertOk()->assertSee('fa-bag-shopping', false);
});
