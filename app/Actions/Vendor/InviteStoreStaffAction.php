<?php

namespace App\Actions\Vendor;

use App\Enums\StoreStaffStatus;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\Store;
use App\Models\StoreStaff;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Throwable;

/**
 * Invites a staff member to a vendor's store: finds-or-creates the User
 * (vendor_staff, unverified until they set a password), links them via
 * StoreStaff (status "invited"), and grants the chosen store.* permissions
 * on their account. Activation (status -> active) happens on their first
 * successful login — see App\Listeners\Vendor\ActivateInvitedStoreStaff.
 */
class InviteStoreStaffAction
{
    /**
     * @param  array<string>  $permissions
     */
    public function handle(Store $store, string $name, string $email, array $permissions): StoreStaff
    {
        $staff = DB::transaction(function () use ($store, $name, $email, $permissions) {
            $user = User::where('email', $email)->first();

            if (! $user) {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(40)),
                    'user_type' => UserType::VendorStaff,
                    'status' => UserStatus::Active,
                ]);
            }

            $staff = StoreStaff::create([
                'store_id' => $store->id,
                'user_id' => $user->id,
                'status' => StoreStaffStatus::Invited,
                'invited_at' => now(),
            ]);

            $user->syncPermissions(
                Permission::where('guard_name', 'vendor')->whereIn('name', $permissions)->get()
            );

            return $staff;
        });

        // See ApproveVendorApplicationAction::sendCredentialsEmail() — the
        // invite itself must persist even if the mail transport is down.
        try {
            Password::broker('vendors')->sendResetLink(['email' => $email]);
        } catch (Throwable $exception) {
            report($exception);
        }

        return $staff;
    }
}
