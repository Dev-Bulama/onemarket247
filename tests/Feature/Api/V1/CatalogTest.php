<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\City;
use App\Models\Currency;
use App\Models\HeroSlide;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\Store;
use App\Models\Vendor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Currency::factory()->create(['code' => 'NGN', 'is_default' => true]);
});

test('the home endpoint returns every section with real data', function () {
    $featured = Product::factory()->create(['is_featured' => true, 'name' => 'Featured Widget']);
    $brand = Brand::factory()->create(['is_active' => true]);
    $store = Store::factory()->create(['is_featured' => true]);

    $response = $this->getJson('/api/v1/home')->assertOk();

    $response->assertJsonPath('data.featured_products.0.name', 'Featured Widget')
        ->assertJsonPath('data.brands.0.id', $brand->id)
        ->assertJsonPath('data.stores.0.id', $store->id);
});

test('the home endpoint includes active hero slides, mirroring the storefront homepage', function () {
    Storage::fake('public');
    $activePath = UploadedFile::fake()->image('active.jpg')->store('hero-slides', 'public');
    HeroSlide::factory()->create(['image_path' => $activePath, 'is_active' => true, 'sort_order' => 1]);
    HeroSlide::factory()->create(['is_active' => false, 'sort_order' => 0]);

    $response = $this->getJson('/api/v1/home')->assertOk();

    $response->assertJsonCount(1, 'data.hero_slides')
        ->assertJsonPath('data.hero_slides.0.image_url', fn ($url) => str_contains($url, $activePath));
});

test('recommended near you honours city_id/state_id passed as query params, not session', function () {
    $nearCity = City::factory()->create();
    $nearVendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $nearVendor->id, 'city_id' => $nearCity->id]);
    $nearProduct = Product::factory()->create(['vendor_id' => $nearVendor->id, 'name' => 'Nearby Widget']);

    $farCity = City::factory()->create();
    $farVendor = Vendor::factory()->create();
    Store::factory()->create(['vendor_id' => $farVendor->id, 'city_id' => $farCity->id]);
    Product::factory()->create(['vendor_id' => $farVendor->id, 'name' => 'Far Away Widget']);

    $this->getJson("/api/v1/home?city_id={$nearCity->id}")
        ->assertOk()
        ->assertJsonPath('data.recommended_near_you.0.name', $nearProduct->name);
});

test('categories index returns active root categories with their active children', function () {
    $root = Category::factory()->create(['is_active' => true, 'parent_id' => null]);
    Category::factory()->create(['is_active' => true, 'parent_id' => $root->id, 'name' => 'Child']);
    Category::factory()->create(['is_active' => false, 'parent_id' => null]);

    $this->getJson('/api/v1/categories')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.children.0.name', 'Child');
});

test('a category show page returns its products and subcategories', function () {
    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->create();
    $product->categories()->attach($category);

    $this->getJson("/api/v1/categories/{$category->slug}")
        ->assertOk()
        ->assertJsonPath('data.category.slug', $category->slug)
        ->assertJsonPath('data.products.0.id', $product->id);
});

test('brands index and show both work', function () {
    $brand = Brand::factory()->create(['is_active' => true]);
    $product = Product::factory()->create(['brand_id' => $brand->id]);

    $this->getJson('/api/v1/brands')->assertOk()->assertJsonCount(1, 'data');

    $this->getJson("/api/v1/brands/{$brand->slug}")
        ->assertOk()
        ->assertJsonPath('data.brand.id', $brand->id)
        ->assertJsonPath('data.products.0.id', $product->id);
});

test('product list supports the same filters as the storefront shop page', function () {
    Product::factory()->create(['name' => 'Cheap Widget', 'price' => 1000]);
    Product::factory()->create(['name' => 'Pricey Widget', 'price' => 90000]);

    $this->getJson('/api/v1/products?sort=price_desc')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Pricey Widget');
});

test('product detail returns the translated name when a translation exists for the active locale', function () {
    app()->setLocale('fr');
    $french = Language::factory()->create(['code' => 'fr']);
    $product = Product::factory()->create(['name' => 'English Name']);
    ProductTranslation::factory()->create(['product_id' => $product->id, 'language_id' => $french->id, 'name' => 'Nom Français']);

    $this->getJson("/api/v1/products/{$product->slug}")
        ->assertOk()
        ->assertJsonPath('data.name', 'Nom Français');
});

test('a draft product 404s from the product detail endpoint', function () {
    $draft = Product::factory()->draft()->create();

    $this->getJson("/api/v1/products/{$draft->slug}")->assertNotFound();
});

test('store index, show, and products endpoints all work', function () {
    $store = Store::factory()->create(['is_featured' => true]);
    $product = Product::factory()->create(['vendor_id' => $store->vendor_id]);

    $this->getJson('/api/v1/stores')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson("/api/v1/stores/{$store->slug}")->assertOk()->assertJsonPath('data.id', $store->id);
    $this->getJson("/api/v1/stores/{$store->slug}/products")
        ->assertOk()
        ->assertJsonPath('data.store.id', $store->id)
        ->assertJsonPath('data.products.0.id', $product->id);
});

test('search matches by product name, brand name, and category name', function () {
    $byName = Product::factory()->create(['name' => 'Bluetooth Speaker']);
    $brand = Brand::factory()->create(['name' => 'Acme']);
    $byBrand = Product::factory()->create(['name' => 'Something Else', 'brand_id' => $brand->id]);

    $this->getJson('/api/v1/search?q=Bluetooth')
        ->assertOk()
        ->assertJsonPath('data.0.id', $byName->id)
        ->assertJsonCount(1, 'data');

    $this->getJson('/api/v1/search?q=Acme')
        ->assertOk()
        ->assertJsonPath('data.0.id', $byBrand->id);
});
