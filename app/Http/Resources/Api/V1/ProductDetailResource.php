<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Api\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Full product-detail shape, mirroring resources/views/storefront/products/show.blade.php.
 * Expects $product->load(['brand', 'categories', 'vendor.store', 'variations.attributeValues.attribute'])
 * already applied by the controller — see App\Http\Controllers\Api\V1\ProductController::show().
 */
class ProductDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $range = $this->displayPriceRange();
        $price = $this->displayPrice();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->translatedName(),
            'short_description' => $this->translatedShortDescription(),
            'description' => $this->translatedDescription(),
            'sku' => $this->sku,
            'images' => $this->getMedia('images')->map(fn ($media) => [
                'url' => $media->getUrl(),
                'thumbnail' => $media->getUrl('thumb') ?: $media->getUrl(),
            ])->values(),
            'brand' => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ] : null,
            'categories' => $this->categories->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])->values(),
            'price' => $price !== null ? Money::make($price) : null,
            'price_range' => $range ? ['min' => Money::make($range['min']), 'max' => Money::make($range['max'])] : null,
            'compare_at_price' => Money::make($this->compare_at_price),
            'discount_percent' => $range ? null : $this->discountPercent(),
            'on_flash_sale' => $this->isOnFlashSale(),
            'flash_sale_ends_at' => $this->flash_sale_ends_at,
            'stock_status' => $this->stock_status->value,
            'in_stock' => $this->isInStock(),
            'manage_stock' => $this->manage_stock,
            'stock_quantity' => $this->manage_stock ? $this->stock_quantity : null,
            'rating' => $this->averageRating(),
            'review_count' => $this->approvedReviews->count(),
            'variations' => $this->variations->map(fn ($variation) => [
                'id' => $variation->id,
                'price' => Money::make($variation->price),
                'in_stock' => $variation->isInStock(),
                'attributes' => $variation->attributeValues->map(fn ($value) => [
                    'attribute' => $value->attribute->name,
                    'value' => $value->value,
                ])->values(),
            ])->values(),
            'vendor' => $this->vendor?->store ? [
                'store_name' => $this->vendor->store->name,
                'store_slug' => $this->vendor->store->slug,
            ] : null,
        ];
    }
}
