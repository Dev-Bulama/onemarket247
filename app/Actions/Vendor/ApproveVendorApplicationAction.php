<?php

namespace App\Actions\Vendor;

use App\Enums\BillingPeriod;
use App\Enums\StoreStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Enums\VendorApplicationStatus;
use App\Enums\VendorStatus;
use App\Enums\VendorSubscriptionStatus;
use App\Exceptions\VendorApplicationConflictException;
use App\Models\Store;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorApplication;
use App\Models\VendorDocument;
use App\Models\VendorSubscription;
use App\Models\VendorSubscriptionPlan;
use App\Models\Warehouse;
use App\Notifications\VendorApplicationApprovedNotification;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * Provisions the User + Vendor + Store + VendorSubscription that an
 * application only implies until now (see
 * docs/architecture/07-vendor-dashboard.md §2). Used identically by both
 * the automatic-approval path (application submission, when
 * vendor.approval_mode = automatic) and an admin's explicit "approve"
 * action, so the two paths can never drift apart.
 */
class ApproveVendorApplicationAction
{
    public function handle(VendorApplication $application, ?User $reviewer = null): Vendor
    {
        $this->assertNoAccountConflict($application);

        $vendor = DB::transaction(function () use ($application, $reviewer) {
            $user = User::create([
                'name' => $application->full_name,
                'email' => $application->email,
                'phone' => $application->phone,
                'password' => Hash::make(Str::random(40)),
                'user_type' => UserType::VendorOwner,
                'status' => UserStatus::Active,
            ]);

            // email_verified_at is intentionally excluded from User's
            // fillable list; approving an application is the platform
            // vouching for the address, same rationale as
            // RegisterOrLoginSocialUserAction.
            $user->forceFill(['email_verified_at' => now()])->save();

            $vendor = Vendor::create([
                'user_id' => $user->id,
                'business_name' => $application->business_name,
                'registration_number' => $application->registration_number,
                'tax_identification_number' => $application->tax_identification_number,
                'identity_type' => $application->identity_type,
                'identity_number' => $application->identity_number,
                'status' => VendorStatus::Approved,
                'bank_name' => $application->bank_name,
                'bank_account_name' => $application->bank_account_name,
                'bank_account_number' => $application->bank_account_number,
                'country_id' => $application->country_id,
                'state_id' => $application->state_id,
                'city_id' => $application->city_id,
                'postal_code' => $application->postal_code,
                'address' => $application->address,
                'approved_at' => now(),
            ]);

            Store::create([
                'vendor_id' => $vendor->id,
                'name' => $application->store_name,
                'slug' => $this->uniqueSlug($application->store_slug),
                'description' => $application->store_description,
                'email' => $application->email,
                'phone' => $application->phone,
                'address' => $application->address,
                'country_id' => $application->country_id,
                'state_id' => $application->state_id,
                'city_id' => $application->city_id,
                'status' => StoreStatus::Active,
            ]);

            // Without a warehouse, this vendor could never actually stock a
            // product — the vendor panel's "Add stock" action only offers a
            // choice of the vendor's own existing warehouses, and nothing
            // else in the app ever creates one. Every vendor needs at least
            // this default one from day one.
            Warehouse::create([
                'vendor_id' => $vendor->id,
                'name' => 'Main Warehouse',
                'code' => 'MAIN',
                'address' => $application->address,
                'country_id' => $application->country_id,
                'state_id' => $application->state_id,
                'city_id' => $application->city_id,
                'is_default' => true,
                'is_active' => true,
            ]);

            $user->assignRole(
                Role::where('name', 'Vendor Owner')->where('guard_name', 'vendor')->first()
            );

            // vendor_application_id is cleared as documents migrate to the new
            // vendor — see the alter-vendor-documents migration's docblock:
            // exactly one of vendor_id/vendor_application_id is meant to be
            // set at a time. Leaving both set would mean deleting this
            // (now-approved, historical) application cascades onto the
            // vendor's live documents via vendor_documents' cascadeOnDelete
            // on vendor_application_id.
            VendorDocument::where('vendor_application_id', $application->id)->update([
                'vendor_id' => $vendor->id,
                'vendor_application_id' => null,
            ]);

            $plan = $application->subscriptionPlan
                ?? VendorSubscriptionPlan::where('is_default', true)->first();

            if ($plan) {
                VendorSubscription::create([
                    'vendor_id' => $vendor->id,
                    'vendor_subscription_plan_id' => $plan->id,
                    'status' => VendorSubscriptionStatus::Active,
                    'starts_at' => now(),
                    'ends_at' => $plan->billing_period === BillingPeriod::Lifetime
                        ? null
                        : now()->addDays($plan->billing_period->durationInDays()),
                ]);
            }

            $application->update([
                'status' => VendorApplicationStatus::Approved,
                'vendor_id' => $vendor->id,
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => now(),
            ]);

            AuditLogger::record('vendor_application.approved', $application, ['status' => 'pending'], ['status' => 'approved', 'vendor_id' => $vendor->id], $reviewer);

            return $vendor;
        });

        $this->sendCredentialsEmail($vendor);

        return $vendor;
    }

    /**
     * Sending the approval email deliberately happens after the
     * transaction commits and can never roll it back: approving a vendor
     * (creating their User/Vendor/Store/Subscription) must succeed even if
     * the mail transport is down or misconfigured — that's an operational
     * problem to fix separately, not a reason to silently undo a real
     * approval and 500 the admin who clicked the button.
     *
     * A plain Password::broker()->sendResetLink() would fire Laravel's
     * stock "click here to reset your password" email, which never
     * mentions the application was approved or that a store now exists —
     * confusing for someone who never asked for a password reset. Instead
     * we mint the same broker token ourselves and hand it to a notification
     * that actually explains what happened.
     */
    private function sendCredentialsEmail(Vendor $vendor): Vendor
    {
        try {
            $user = $vendor->user;
            $token = Password::broker('vendors')->createToken($user);
            $user->notify(new VendorApplicationApprovedNotification($vendor, $token));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $vendor;
    }

    /**
     * The submission form validates email/phone uniqueness at the moment
     * of applying, but time passes between submission and an admin
     * clicking "Approve" — a conflicting account can appear in the
     * meantime (e.g. the applicant, or someone else, registers a customer
     * account with the same phone). Checking here turns that into an
     * actionable admin-facing message instead of an uncaught
     * SQLSTATE[23000] unique-constraint crash from User::create().
     */
    private function assertNoAccountConflict(VendorApplication $application): void
    {
        if ($application->phone && User::where('phone', $application->phone)->exists()) {
            throw new VendorApplicationConflictException(
                "Cannot approve: phone number \"{$application->phone}\" is already used by another account. Update the phone number on this application (or on the conflicting account) before approving."
            );
        }

        if (User::where('email', $application->email)->exists()) {
            throw new VendorApplicationConflictException(
                "Cannot approve: email \"{$application->email}\" is already used by another account. Resolve the conflicting account before approving."
            );
        }
    }

    private function uniqueSlug(string $slug): string
    {
        $original = $slug;
        $suffix = 1;

        while (Store::withoutGlobalScopes()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$suffix++;
        }

        return $slug;
    }
}
