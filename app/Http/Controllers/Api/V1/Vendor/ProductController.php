<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\StockStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\VendorProductResource;
use App\Models\Product;
use App\Support\Api\ApiResponse;
use App\Support\Api\Paginated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Product creation isn't exposed here yet — it needs image/media upload
 * handling (see App\Filament\Vendor\Resources\Products\Concerns\HandlesProductMedia)
 * which a JSON API needs a genuinely different (multipart) shape for; this
 * covers the quick-edit fields a vendor would realistically change from
 * the app (price, stock, description) without reinventing that flow half-done.
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
}
