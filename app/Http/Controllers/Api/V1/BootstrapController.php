<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * The one call the mobile app makes at cold start, always against its own
 * hardcoded PRODUCTION_API_URL (see mobile/src/config/api.ts) — the only
 * URL guaranteed reachable before the app knows anything else. Everything
 * it gets back is admin-controlled via App\Filament\Pages\AppSettings, so
 * the app's real API base URL, branding, and minimum supported version can
 * all change without shipping a new build. See App\Models\AppSetting's
 * docblock for the full rationale.
 */
class BootstrapController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $settings = AppSetting::current();

        return ApiResponse::success([
            'api_base_url' => $settings->resolveApiBaseUrl(),
            'app_name' => $settings->app_name ?: config('app.name'),
            'logo_url' => $settings->logo_url,
            'splash_logo_url' => $settings->splash_logo_url,
            'min_app_version' => $settings->min_app_version,
            'product_grid_columns' => $settings->product_grid_columns,
        ]);
    }
}
