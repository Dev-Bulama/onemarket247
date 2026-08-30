<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\UserType;
use App\Enums\VendorSubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\VendorSubscriptionPlanResource;
use App\Http\Resources\Api\V1\VendorSubscriptionResource;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use App\Models\VendorSubscriptionPlan;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Mirrors App\Filament\Vendor\Pages\Subscription exactly, including its
 * owner-only access (canAccess()) and its free-plan-only switch constraint
 * — there is no payment collection mechanism yet, so a paid-plan switch
 * attempt is a graceful non-error response, not a 422/500.
 */
class SubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeOwner($request);

        $vendor = Vendor::findOrFail($request->user()->actingVendorId());

        $plans = VendorSubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
        $current = $vendor->currentSubscription()?->load('plan');

        return ApiResponse::success([
            'plans' => VendorSubscriptionPlanResource::collection($plans),
            'current' => $current ? new VendorSubscriptionResource($current) : null,
        ]);
    }

    public function switchTo(Request $request): JsonResponse
    {
        $this->authorizeOwner($request);

        $data = $request->validate([
            'plan_id' => ['required', 'integer'],
        ]);

        $plan = VendorSubscriptionPlan::where('is_active', true)->findOrFail($data['plan_id']);

        if (! $plan->isFree()) {
            return ApiResponse::success(
                ['switched' => false, 'requires_contact_support' => true],
                message: 'Contact support to upgrade to a paid plan.',
            );
        }

        $vendor = Vendor::findOrFail($request->user()->actingVendorId());

        VendorSubscription::where('vendor_id', $vendor->id)
            ->where('status', VendorSubscriptionStatus::Active)
            ->update(['status' => VendorSubscriptionStatus::Cancelled, 'cancelled_at' => now()]);

        $subscription = VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'vendor_subscription_plan_id' => $plan->id,
            'status' => VendorSubscriptionStatus::Active,
            'starts_at' => now(),
        ]);

        return ApiResponse::success(
            ['switched' => true, 'requires_contact_support' => false, 'subscription' => new VendorSubscriptionResource($subscription->load('plan'))],
            message: 'Subscription plan updated.',
        );
    }

    private function authorizeOwner(Request $request): void
    {
        abort_unless($request->user()->user_type === UserType::VendorOwner, 403, 'Only the store owner can manage the subscription.');
    }
}
