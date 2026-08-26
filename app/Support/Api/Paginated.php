<?php

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Wraps a paginator through the given API Resource class and into
 * ApiResponse::success() with the standard pagination meta block
 * documented in docs/architecture/08-api-endpoints.md §13. $extraData
 * lets a "show" endpoint (e.g. a category/brand page's product listing)
 * include sibling data — the parent category, subcategories, etc. —
 * alongside the paginated collection under a 'products' key.
 */
class Paginated
{
    /**
     * @param  class-string<JsonResource>  $resourceClass
     * @param  array<string, mixed>  $extraData
     */
    public static function response(LengthAwarePaginator $paginator, string $resourceClass, array $extraData = [], string $key = 'data'): JsonResponse
    {
        $data = $extraData === []
            ? $resourceClass::collection($paginator->items())
            : [...$extraData, $key => $resourceClass::collection($paginator->items())];

        return ApiResponse::success(
            $data,
            [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ],
        );
    }
}
