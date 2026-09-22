<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Actions\Inventory\AdjustStockAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\VendorProductVariationResource;
use App\Models\Product;
use App\Models\ProductVariation;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Mobile-app equivalent of the vendor Filament panel's VariationsRelationManager
 * (App\Filament\Vendor\Resources\Products\RelationManagers\VariationsRelationManager)
 * — same authorization (a variation is managed exactly like the product it
 * belongs to), same initial-stock seeding via AdjustStockAction (see that
 * relation manager's seedInitialVariationStock() docblock for why a plain
 * stock_quantity column write isn't enough on its own).
 */
class ProductVariationController extends Controller
{
    public function index(Request $request, Product $product): JsonResponse
    {
        Gate::authorize('view', $product);

        return ApiResponse::success(
            VendorProductVariationResource::collection($product->variations()->with('attributeValues.attribute')->get())
        );
    }

    public function store(Request $request, Product $product): JsonResponse
    {
        Gate::authorize('update', $product);

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:255', 'unique:product_variations,sku'],
            'price' => ['required', 'integer', 'min:0'],
            'compare_at_price' => ['nullable', 'integer', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'attribute_value_ids' => ['nullable', 'array'],
            'attribute_value_ids.*' => ['integer', 'exists:attribute_values,id'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $attributeValueIds = $validated['attribute_value_ids'] ?? [];
        $image = $request->file('image');
        unset($validated['attribute_value_ids'], $validated['image']);

        $validated['product_id'] = $product->id;
        $validated['manage_stock'] = true;
        $validated['is_active'] = $validated['is_active'] ?? true;
        $initialQuantity = $validated['stock_quantity'] ?? 0;

        $variation = ProductVariation::create($validated);
        $variation->attributeValues()->sync($attributeValueIds);

        $warehouse = $product->vendor?->defaultWarehouse();

        if ($warehouse) {
            app(AdjustStockAction::class)->handle($warehouse, $variation, $initialQuantity, 'Initial stock at variation creation', $request->user());
        }

        if ($image) {
            $variation->addMedia($image)->toMediaCollection('images');
        }

        return ApiResponse::success(new VendorProductVariationResource($variation->fresh('attributeValues.attribute')), status: 201);
    }

    public function update(Request $request, Product $product, ProductVariation $variation): JsonResponse
    {
        Gate::authorize('update', $product);
        abort_unless($variation->product_id === $product->id, 404);

        $validated = $request->validate([
            'sku' => ['required', 'string', 'max:255', 'unique:product_variations,sku,'.$variation->id],
            'price' => ['required', 'integer', 'min:0'],
            'compare_at_price' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'attribute_value_ids' => ['nullable', 'array'],
            'attribute_value_ids.*' => ['integer', 'exists:attribute_values,id'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);

        $attributeValueIds = $validated['attribute_value_ids'] ?? null;
        $image = $request->file('image');
        unset($validated['attribute_value_ids'], $validated['image']);

        $variation->update($validated);

        if ($attributeValueIds !== null) {
            $variation->attributeValues()->sync($attributeValueIds);
        }

        if ($image) {
            $variation->clearMediaCollection('images');
            $variation->addMedia($image)->toMediaCollection('images');
        }

        return ApiResponse::success(new VendorProductVariationResource($variation->fresh('attributeValues.attribute')));
    }

    public function destroy(Product $product, ProductVariation $variation): JsonResponse
    {
        Gate::authorize('update', $product);
        abort_unless($variation->product_id === $product->id, 404);

        $variation->delete();

        return ApiResponse::success(null, message: 'Variation deleted.');
    }
}
