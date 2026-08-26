<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Language;
use App\Models\State;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReferenceDataController extends Controller
{
    public function languages(): JsonResponse
    {
        return ApiResponse::success(
            Language::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'native_name', 'direction', 'is_default'])
        );
    }

    public function currencies(): JsonResponse
    {
        return ApiResponse::success(
            Currency::where('is_active', true)->orderBy('code')->get(['id', 'code', 'symbol', 'is_default'])
        );
    }

    public function countries(): JsonResponse
    {
        return ApiResponse::success(
            Country::where('is_active', true)->orderBy('name')->get(['id', 'name', 'iso2', 'phone_code'])
        );
    }

    public function states(Country $country): JsonResponse
    {
        return ApiResponse::success(
            State::where('country_id', $country->id)->where('is_active', true)->orderBy('name')->get(['id', 'name'])
        );
    }

    public function cities(State $state): JsonResponse
    {
        return ApiResponse::success(
            City::where('state_id', $state->id)->where('is_active', true)->orderBy('name')->get(['id', 'name'])
        );
    }
}
