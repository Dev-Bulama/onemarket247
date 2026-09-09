<?php

namespace App\Actions\Account;

use App\Enums\UserStatus;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Self-service account deletion (Google Play "User Data" policy requires
 * an in-app path to delete an account and its data — see
 * docs mentioned in the Terms/Privacy legal pages).
 *
 * Anonymizes rather than hard-deletes the User row: orders.customer_id is
 * restrictOnDelete() (a real order can never be orphaned for accounting
 * purposes) and product_reviews.customer_id is cascadeOnDelete() (a real
 * delete would silently wipe reviews other shoppers rely on). Historical
 * orders already snapshot the customer's name/address as plain columns at
 * checkout time (see Order's shipping_* columns), so anonymizing the User
 * row here never rewrites what past orders show — only future references
 * to this account (e.g. an admin looking the customer up) see the
 * anonymized identity. Data the customer exclusively controls (addresses,
 * wishlist, compare list, carts, device tokens, linked social accounts)
 * is deleted outright.
 */
class DeleteAccountAction
{
    public function handle(User $user): void
    {
        DB::transaction(function () use ($user) {
            $before = $user->only(['name', 'email', 'phone', 'status']);

            $user->tokens()->delete();
            $user->socialAccounts()->delete();
            $user->addresses()->delete();
            $user->wishlist()?->delete();
            $user->compareList()?->delete();
            $user->carts()->delete();
            $user->deviceTokens()->delete();

            $user->update([
                'name' => 'Deleted User',
                'email' => 'deleted-'.$user->id.'-'.Str::random(8).'@deleted.onemarket247.local',
                'phone' => null,
                'password' => Hash::make(Str::random(60)),
                'email_verified_at' => null,
                'status' => UserStatus::Deleted,
            ]);

            AuditLogger::record('customer.deleted_own_account', $user, $before, $user->only(['name', 'email', 'phone', 'status']));
        });
    }
}
