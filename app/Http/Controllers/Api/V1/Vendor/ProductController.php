<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Actions\Product\SubmitProductForApprovalAction;
use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Enums\StockStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\VendorProductResource;
use App\Models\Product;
use App\Support\Api\ApiResponse;
use App\Support\Api\Paginated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Create/delete are a genuinely different (multipart, media-uploading)
 * shape than update — see store()'s docblock — which is why they're
 * separated from the quick-edit fields (price, stock, description) update()
 * covers.
 */
class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $vendorId = $request->user()->actingVendorId();

        $products = Product::where('vendor_id', $vendorId)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->value()))
            ->latest()
            ->paginate(20);

        return Paginated::response($products, VendorProductResource::class);
    }

    public function show(Request $request, Product $product): JsonResponse
    {
        Gate::authorize('view', $product);

        return ApiResponse::success(new VendorProductResource($product));
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        Gate::authorize('update', $product);

        $validated = $request->validate([
            'short_description' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'compare_at_price' => ['nullable', 'integer', 'min:0'],
            'manage_stock' => ['required', 'boolean'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'stock_status' => ['required', Rule::enum(StockStatus::class)],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ]);

        $product->update($validated);

        return ApiResponse::success(new VendorProductResource($product->fresh()));
    }

    /**
     * Mirrors App\Filament\Vendor\Resources\Products\Pages\CreateProduct::
     * handleRecordCreation() — vendor_id and status are always injected,
     * never accepted from the client — plus image uploads, which a single
     * multipart API request can attach directly (see App\Models\
     * ProductReview / SubmitReviewAction for the same addMedia() pattern);
     * unlike the Filament form there's no tmp-staging dance since the
     * record and its files arrive together.
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Product::class);

        $validated = $request->validate([
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'shipping_class_id' => ['nullable', 'integer', 'exists:shipping_classes,id'],
            'tax_class_id' => ['nullable', 'integer', 'exists:tax_classes,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku'],
            'type' => ['nullable', Rule::enum(ProductType::class)],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:product_tags,id'],
            'short_description' => ['nullable', 'string', 'max:2000'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'integer', 'min:0', 'required_unless:type,variable'],
            'compare_at_price' => ['nullable', 'integer', 'min:0'],
            'manage_stock' => ['nullable', 'boolean'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'stock_status' => ['nullable', Rule::enum(StockStatus::class), 'required_unless:type,variable'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric'],
            'length' => ['nullable', 'numeric'],
            'width' => ['nullable', 'numeric'],
            'height' => ['nullable', 'numeric'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:255'],
            'images' => ['nullable', 'array', 'max:8'],
            'images.*' => ['image', 'max:5120'],
        ]);

        $categories = $validated['categories'] ?? null;
        $tags = $validated['tags'] ?? null;
        $images = $request->file('images', []);
        unset($validated['categories'], $validated['tags'], $validated['images']);

        $validated['slug'] = $validated['slug'] ?? $this->uniqueSlug($validated['name']);
        $validated['type'] = $validated['type'] ?? ProductType::Simple->value;
        $validated['manage_stock'] = $validated['manage_stock'] ?? true;
        $validated['vendor_id'] = $request->user()->actingVendorId();
        $validated['status'] = ProductStatus::Draft->value;

        /** @var Product $product */
        $product = Product::create($validated);

        if ($categories !== null) {
            $product->categories()->sync($categories);
        }

        if ($tags !== null) {
            $product->tags()->sync($tags);
        }

        foreach ($images as $image) {
            $product->addMedia($image)->toMediaCollection('images');
        }

        return ApiResponse::success(new VendorProductResource($product->fresh()), status: 201);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        Gate::authorize('delete', $product);

        $product->delete();

        return ApiResponse::success(message: 'Product deleted.');
    }

    /**
     * Mirrors App\Filament\Vendor\Resources\Products\Tables\ProductsTable's
     * "Submit for review" row action — moves a Draft/Rejected product to
     * PendingApproval (or straight to Published under automatic approval
     * mode), same App\Actions\Product\SubmitProductForApprovalAction. Only
     * a Draft/Rejected product can be submitted; a product not in either
     * state fails the enum check the action doesn't itself enforce, so
     * that's checked here instead.
     */
    public function submit(Request $request, Product $product, SubmitProductForApprovalAction $action): JsonResponse
    {
        Gate::authorize('update', $product);

        abort_unless(
            in_array($product->status, [ProductStatus::Draft, ProductStatus::Rejected], true),
            422,
            'Only a draft or rejected product can be submitted for review.',
        );

        return ApiResponse::success(new VendorProductResource($action->handle($product)));
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 1;

        while (Product::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
