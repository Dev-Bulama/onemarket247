<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Disclaimer\RecordDisclaimerAcceptanceAction;
use App\Actions\Disclaimer\ResolveActiveDisclaimerAction;
use App\Enums\DisclaimerTrigger;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DisclaimerResource;
use App\Models\Disclaimer;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Priority 9's disclaimer pop-up system, for the mobile app — mirrors
 * App\Http\Controllers\DisclaimerAcceptanceController and the web
 * middleware that resolves the general-browsing one, but stateless: a
 * mobile guest has no cookie jar, so it persists its own `guest_id` (a
 * device-generated UUID) and sends it explicitly, exactly like
 * App\Http\Controllers\Api\V1\Concerns\ResolvesApiCart's `cart_token`
 * does for a guest cart.
 */
class DisclaimerController extends Controller
{
    public function general(Request $request, ResolveActiveDisclaimerAction $action): JsonResponse
    {
        $user = $request->user('sanctum');
        $guestId = $user ? null : ($request->string('guest_id')->value() ?: null);

        $disclaimer = $action->forGeneralBrowsing($user, $guestId);

        return ApiResponse::success($disclaimer ? new DisclaimerResource($disclaimer) : null);
    }

    public function active(Request $request, ResolveActiveDisclaimerAction $action): JsonResponse
    {
        $data = $request->validate([
            'trigger' => ['required', Rule::enum(DisclaimerTrigger::class)],
            'guest_id' => ['nullable', 'string'],
        ]);

        $user = $request->user('sanctum');
        $guestId = $user ? null : ($data['guest_id'] ?? null);

        $disclaimer = $action->handle(DisclaimerTrigger::from($data['trigger']), $user, $guestId);

        return ApiResponse::success($disclaimer ? new DisclaimerResource($disclaimer) : null);
    }

    public function accept(Request $request, Disclaimer $disclaimer, RecordDisclaimerAcceptanceAction $action): JsonResponse
    {
        $user = $request->user('sanctum');
        $guestId = $user ? null : ($request->string('guest_id')->value() ?: null);

        $action->handle($disclaimer, $user, $guestId, $request);

        return ApiResponse::success(message: 'Recorded.');
    }
}
