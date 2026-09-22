<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * The variation-eligible attributes (Colour, Size, etc.) and their values,
 * for the mobile vendor app's "add a variation" picker — the web Filament
 * equivalent is VariationsRelationManager's CheckboxList, which reads the
 * same is_variation flag.
 */
class AttributeController extends Controller
{
    public function index(): JsonResponse
    {
        $attributes = Attribute::where('is_variation', true)
            ->with('values')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Attribute $attribute) => [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'values' => $attribute->values->map(fn ($value) => [
                    'id' => $value->id,
                    'value' => $value->value,
                ])->values(),
            ]);

        return ApiResponse::success($attributes);
    }
}
