<?php

namespace App\Actions\Product;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Support\Collection;

/**
 * Products from vendors whose store is in the given city (falling back to
 * state, then to generally popular products) — genuinely location-driven,
 * never a fabricated "near you" claim. Shared by the web homepage (which
 * resolves city/state from the session set by LocationController::switch())
 * and the mobile API's /home endpoint (which receives them as explicit
 * query params, since a bearer-token API request carries no session) so
 * the two surfaces can never define "near you" differently.
 */
class RecommendedNearYouAction
{
    public function handle(?int $cityId, ?int $stateId, int $limit = 12): Collection
    {
        $base = fn () => Product::query()
            ->where('status', ProductStatus::Published)
            ->with(['brand', 'media', 'vendor.store.city'])
            ->withCount('approvedReviews');

        if ($cityId) {
            $matches = $base()->whereHas('vendor.store', fn ($q) => $q->where('city_id', $cityId))
                ->orderByDesc('view_count')->take($limit)->get();

            if ($matches->isNotEmpty()) {
                return $matches;
            }
        }

        if ($stateId) {
            $matches = $base()->whereHas('vendor.store', fn ($q) => $q->where('state_id', $stateId))
                ->orderByDesc('view_count')->take($limit)->get();

            if ($matches->isNotEmpty()) {
                return $matches;
            }
        }

        return $base()->orderByDesc('view_count')->take($limit)->get();
    }
}
