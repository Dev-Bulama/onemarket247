<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Concerns\FiltersProducts;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Support\Api\Paginated;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    use FiltersProducts;

    public function __invoke(Request $request): JsonResponse
    {
        $term = trim((string) $request->string('q'));

        $query = Product::query();

        if ($term !== '') {
            $query->where(function (Builder $q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('categories', fn (Builder $c) => $c->where('name', 'like', "%{$term}%"));
            });
        }

        $products = $this->filteredProducts($query, $request);

        return Paginated::response($products, ProductResource::class);
    }
}
