<?php

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Filament\Vendor\Resources\Products\Pages\CreateProduct;
use App\Filament\Vendor\Resources\Products\Pages\EditProduct;
use App\Filament\Vendor\Resources\Products\Pages\ListProducts;
use App\Filament\Vendor\Resources\Products\RelationManagers\TranslationsRelationManager;
use App\Filament\Vendor\Resources\Products\RelationManagers\VariationsRelationManager;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductTag;
use App\Models\Store;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    (new RolePermissionSeeder)->run();
    Filament::setCurrentPanel('vendor');
});

test('vendor owner can load their product catalog pages', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $category = Category::factory()->create();
    $product = Product::factory()->for($vendor)->create();
    $product->categories()->attach($category->id);

    $this->actingAs($vendor->user, 'vendor')->get('/vendor/products')->assertOk();
    $this->actingAs($vendor->user, 'vendor')->get('/vendor/products/create')->assertOk();
    $this->actingAs($vendor->user, 'vendor')->get("/vendor/products/{$product->id}/edit")->assertOk();
});

test('a vendor cannot open another vendors product edit page', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();

    $otherVendor = Vendor::factory()->create();
    Store::factory()->for($otherVendor)->create();
    $otherProduct = Product::factory()->for($otherVendor)->create();

    $this->actingAs($vendor->user, 'vendor')
        ->get("/vendor/products/{$otherProduct->id}/edit")->assertNotFound();
});

test('a vendor can create a product with categories, tags and images through the form', function () {
    Storage::fake('public');

    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();

    $categoryA = Category::factory()->create();
    $categoryB = Category::factory()->create();
    $tag = ProductTag::factory()->create();

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(CreateProduct::class)
        ->fillForm([
            'name' => 'Test Widget',
            'slug' => 'test-widget',
            'sku' => 'WIDGET-1',
            'type' => ProductType::Simple->value,
            'categories' => [$categoryA->id, $categoryB->id],
            'tags' => [$tag->id],
            'price' => 1999,
            'stock_quantity' => 5,
            'stock_status' => 'in_stock',
            'images' => [UploadedFile::fake()->image('widget.jpg')->store('tmp-product-media', 'public')],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::where('slug', 'test-widget')->firstOrFail();

    expect($product->vendor_id)->toBe($vendor->id)
        ->and($product->status)->toBe(ProductStatus::Draft)
        ->and($product->categories()->pluck('categories.id')->sort()->values()->all())
        ->toEqual(collect([$categoryA->id, $categoryB->id])->sort()->values()->all())
        ->and($product->tags()->pluck('product_tags.id')->all())->toEqual([$tag->id])
        ->and($product->getMedia('images'))->toHaveCount(1);
});

test('a vendor can submit a draft product for review from the table', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->for($vendor)->draft()->create();

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(ListProducts::class)
        ->callTableAction('submitForReview', $product);

    expect($product->fresh()->status)->toBe(ProductStatus::PendingApproval);
});

test('the submit for review action is hidden for a published product', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->for($vendor)->create();

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(ListProducts::class)
        ->assertTableActionHidden('submitForReview', $product);
});

test('a vendor can add a variation with attribute values through the relation manager', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->for($vendor)->variable()->create();

    $attribute = Attribute::factory()->create(['is_variation' => true]);
    $value = AttributeValue::factory()->for($attribute)->create();

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(VariationsRelationManager::class, [
            'ownerRecord' => $product,
            'pageClass' => EditProduct::class,
        ])
        ->callTableAction('create', data: [
            'sku' => 'VAR-1',
            'price' => 999,
            'stock_quantity' => 3,
            'attributeValues' => [$value->id],
        ]);

    expect($product->variations()->count())->toBe(1)
        ->and($product->variations()->first()->attributeValues()->pluck('attribute_values.id')->all())
        ->toEqual([$value->id]);
});

test('a vendor can add a translation for their product through the relation manager', function () {
    $vendor = Vendor::factory()->create();
    Store::factory()->for($vendor)->create();
    $product = Product::factory()->for($vendor)->create();

    $language = Language::factory()->create(['code' => 'fr']);

    Livewire::actingAs($vendor->user, 'vendor')
        ->test(TranslationsRelationManager::class, [
            'ownerRecord' => $product,
            'pageClass' => EditProduct::class,
        ])
        ->callTableAction('create', data: [
            'language_id' => $language->id,
            'name' => 'Nom Français',
            'short_description' => 'Courte description',
        ]);

    expect($product->translations()->count())->toBe(1)
        ->and($product->translatedName('fr'))->toBe('Nom Français');
});
