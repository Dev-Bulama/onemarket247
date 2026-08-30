<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The mobile app calls store() once it has a OneSignal player id and the
 * user is logged in (see mobile/src/store/pushStore.ts), and destroy() on
 * logout so a shared/reinstalled device stops being addressable as that
 * user. `token` is globally unique (see the device_tokens migration) —
 * re-registering the same physical device under a different account
 * simply reassigns the row's user_id rather than erroring.
 */
class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'string', 'max:50'],
        ]);

        // `token` is unique platform-wide (see the device_tokens migration),
        // not scoped to a user — querying through $request->user()->deviceTokens()
        // would miss a row already owned by a different account and then
        // crash on the unique-constraint collision trying to insert a
        // duplicate. Querying DeviceToken directly reassigns it instead.
        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            ['user_id' => $request->user()->id, 'platform' => $data['platform'] ?? null],
        );

        return ApiResponse::success(message: 'Device registered.', status: 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string']]);

        $request->user()->deviceTokens()->where('token', $data['token'])->delete();

        return ApiResponse::success(message: 'Device unregistered.');
    }
}
