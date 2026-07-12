<?php

namespace App\Filament\Vendor\Pages;

use App\Enums\UserType;
use App\Enums\VendorSubscriptionStatus;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use App\Models\VendorSubscriptionPlan;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class Subscription extends Page
{
    protected string $view = 'filament.vendor.pages.subscription';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return Auth::guard('vendor')->user()->user_type === UserType::VendorOwner;
    }

    public function getVendor(): Vendor
    {
        $vendorId = Auth::guard('vendor')->user()->actingVendorId();

        return Vendor::findOrFail($vendorId);
    }

    public function getPlans(): Collection
    {
        return VendorSubscriptionPlan::where('is_active', true)->orderBy('sort_order')->get();
    }

    /**
     * Only free plans can be switched to directly from this page — there is
     * no payment collection mechanism yet (that lands with Phase 13), so a
     * paid-plan "upgrade" button would not actually be able to charge
     * anyone. Switching to a free plan is fully self-service and real.
     */
    public function switchTo(int $planId): void
    {
        $plan = VendorSubscriptionPlan::where('is_active', true)->findOrFail($planId);

        if (! $plan->isFree()) {
            Notification::make()
                ->title('Contact support to upgrade to a paid plan')
                ->warning()
                ->send();

            return;
        }

        $vendor = $this->getVendor();

        VendorSubscription::where('vendor_id', $vendor->id)
            ->where('status', VendorSubscriptionStatus::Active)
            ->update(['status' => VendorSubscriptionStatus::Cancelled, 'cancelled_at' => now()]);

        VendorSubscription::create([
            'vendor_id' => $vendor->id,
            'vendor_subscription_plan_id' => $plan->id,
            'status' => VendorSubscriptionStatus::Active,
            'starts_at' => now(),
        ]);

        Notification::make()->title('Subscription plan updated')->success()->send();
    }
}
