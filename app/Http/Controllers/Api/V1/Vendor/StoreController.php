<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\StoreStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\StoreResource;
use App\Models\Store;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(new StoreResource($this->store($request)));
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'status' => ['required', Rule::in([StoreStatus::Active->value, StoreStatus::Vacation->value])],
            'vacation_message' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:255'],
        ]);

        $store = $this->store($request);
        $store->update($validated);

        return ApiResponse::success(new StoreResource($store->fresh(['city', 'state', 'country'])));
    }

    private function store(Request $request): Store
    {
        $vendorId = $request->user()->actingVendorId();

        return Store::withoutGlobalScopes()->where('vendor_id', $vendorId)->with(['city', 'state', 'country'])->firstOrFail();
    }
}
