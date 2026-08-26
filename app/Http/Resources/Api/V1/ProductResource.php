<?php

namespace App\Http\Resources\Api\V1;

use App\Support\Api\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The card/list shape used by every product-listing endpoint (home
 * sections, category/brand/store listings, search). Mirrors exactly what
 * resources/views/storefront/partials/product-card.blade.php renders, so
 * web and mobile can never show different data for the same product.
 */
class ProductResource extends JsonResource
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
            'sku' => $this->sku,
            'thumbnail' => $this->getFirstMediaUrl('images', 'thumb') ?: $this->getFirstMediaUrl('images') ?: null,
            'brand' => $this->whenLoaded('brand', fn () => $this->brand ? [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'slug' => $this->brand->slug,
            ] : null),
            'price' => $price !== null ? Money::make($price) : null,
            'price_range' => $range ? ['min' => Money::make($range['min']), 'max' => Money::make($range['max'])] : null,
            'compare_at_price' => Money::make($this->compare_at_price),
            'discount_percent' => $range ? null : $this->discountPercent(),
            'on_flash_sale' => $this->isOnFlashSale(),
            'stock_status' => $this->stock_status->value,
            'in_stock' => $this->isInStock(),
            'rating' => $this->averageRating(),
            'review_count' => $this->approved_reviews_count ?? $this->approvedReviews()->count(),
            'vendor' => $this->whenLoaded('vendor', fn () => $this->vendor?->store ? [
                'store_name' => $this->vendor->store->name,
                'store_slug' => $this->vendor->store->slug,
                'city' => $this->vendor->store->relationLoaded('city') ? $this->vendor->store->city?->name : null,
            ] : null),
        ];
    }
}
