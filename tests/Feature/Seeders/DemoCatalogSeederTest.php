<?php

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Database\Seeders\DemoCatalogSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;

test('seeds 25 categories with icons, 25 brands, and 25 products with images', function () {
    Artisan::call('db:seed', ['--class' => DemoCatalogSeeder::class]);

    expect(Product::where('sku', 'like', 'DEMO-%')->count())->toBe(25)
        ->and(Category::whereIn('name', array_keys(DemoCatalogSeeder::CATEGORIES))->count())->toBe(25)
        ->and(Brand::whereIn('name', DemoCatalogSeeder::BRANDS)->count())->toBe(25);

    $category = Category::whereIn('name', array_keys(DemoCatalogSeeder::CATEGORIES))->first();
    expect($category->icon)->not->toBeNull();

    $product = Product::where('sku', 'like', 'DEMO-%')->first();
    expect($product->getFirstMediaUrl('images'))->not->toBe('');
});

test('the product thumbnail conversion never touches the queue, so a down queue backend cannot crash the seeder', function () {
    Queue::fake();

    Artisan::call('db:seed', ['--class' => DemoCatalogSeeder::class]);

    Queue::assertNothingPushed();
});

test('demo-catalog:reset clears the seeded data so the seeder can run again without unique constraint errors', function () {
    Artisan::call('db:seed', ['--class' => DemoCatalogSeeder::class]);

    Artisan::call('demo-catalog:reset', ['--force' => true]);

    expect(Product::where('sku', 'like', 'DEMO-%')->count())->toBe(0)
        ->and(Category::whereIn('name', array_keys(DemoCatalogSeeder::CATEGORIES))->count())->toBe(0);

    Artisan::call('db:seed', ['--class' => DemoCatalogSeeder::class]);

    expect(Product::where('sku', 'like', 'DEMO-%')->count())->toBe(25);
});
