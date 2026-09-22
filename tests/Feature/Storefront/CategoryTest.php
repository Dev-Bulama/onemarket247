<?php

use App\Enums\SpotlightDisplayArea;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductSpotlight;

test('the category index page lists active root categories with their children', function () {
    $root = Category::factory()->create(['is_active' => true]);
    $child = Category::factory()->childOf($root)->create(['is_active' => true]);

    $response = $this->get('/categories');

    $response->assertOk()->assertSee($root->name)->assertSee($child->name);
});

test('a category page lists products filed directly under it', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create();
    $product->categories()->attach($category->id);

    $response = $this->get(route('categories.show', $category));

    $response->assertOk()->assertSee($product->name);
});

test('a category page includes products from its subcategories', function () {
    $root = Category::factory()->create();
    $child = Category::factory()->childOf($root)->create();
    $grandchild = Category::factory()->childOf($child)->create();

    $product = Product::factory()->create();
    $product->categories()->attach($grandchild->id);

    $response = $this->get(route('categories.show', $root));

    $response->assertOk()->assertSee($product->name);
});

test('a subcategory page only lists that subcategorys own products', function () {
    $root = Category::factory()->create();
    $childA = Category::factory()->childOf($root)->create();
    $childB = Category::factory()->childOf($root)->create();

    $productA = Product::factory()->create();
    $productA->categories()->attach($childA->id);
    $productB = Product::factory()->create();
    $productB->categories()->attach($childB->id);

    $response = $this->get(route('categories.show-subcategory', [$root, $childA]));

    $response->assertOk()->assertSee($productA->name)->assertDontSee($productB->name);
});

test('a category page shows products spotlighted for that category', function () {
    $category = Category::factory()->create();
    $spotlighted = Product::factory()->create(['name' => 'Category Spotlight Widget']);
    ProductSpotlight::factory()->forArea(SpotlightDisplayArea::Category)->create([
        'product_id' => $spotlighted->id,
        'category_id' => $category->id,
    ]);

    $response = $this->get(route('categories.show', $category));

    $response->assertOk()->assertSeeInOrder(['Spotlight', $spotlighted->name]);
});

test('a category spotlight does not leak onto a different category page', function () {
    $category = Category::factory()->create();
    $otherCategory = Category::factory()->create();
    $spotlighted = Product::factory()->create(['name' => 'Other Category Spotlight Widget']);
    ProductSpotlight::factory()->forArea(SpotlightDisplayArea::Category)->create([
        'product_id' => $spotlighted->id,
        'category_id' => $otherCategory->id,
    ]);

    $response = $this->get(route('categories.show', $category));

    $response->assertOk()->assertDontSee('Other Category Spotlight Widget');
});

test('a subcategory url 404s when the subcategory does not belong to the parent', function () {
    $root = Category::factory()->create();
    $unrelated = Category::factory()->create();

    $this->get(route('categories.show-subcategory', [$root, $unrelated]))->assertNotFound();
});
