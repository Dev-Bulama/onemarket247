<?php

namespace App\Actions\Disclaimer;

use App\Enums\DisclaimerTrigger;
use App\Models\Disclaimer;
use App\Models\DisclaimerAcceptance;
use App\Models\User;
use Illuminate\Support\Facades\Session;

/**
 * Finds the disclaimer (if any) that should still be shown to this
 * visitor for a given trigger — the current active disclaimer targeting
 * it, minus whichever ones they've already accepted. See
 * DisclaimerTrigger's own docblock for how "already accepted" is judged
 * differently for a session-scoped trigger vs. a persistent one.
 */
class ResolveActiveDisclaimerAction
{
    public function handle(DisclaimerTrigger $trigger, ?User $user, ?string $guestIdentifier): ?Disclaimer
    {
        $disclaimer = Disclaimer::currentlyActive()
            ->where('trigger', $trigger->value)
            ->latest('id')
            ->first();

        if (! $disclaimer || $this->isAccepted($disclaimer, $user, $guestIdentifier)) {
            return null;
        }

        return $disclaimer;
    }

    /**
     * The single general-browsing disclaimer (if any) to show on any
     * storefront page — first_visit, once_per_session, and once_per_user
     * all compete for the same one slot, most specific-sounding first.
     */
    public function forGeneralBrowsing(?User $user, ?string $guestIdentifier): ?Disclaimer
    {
        foreach (DisclaimerTrigger::generalTriggers() as $trigger) {
            $disclaimer = $this->handle($trigger, $user, $guestIdentifier);

            if ($disclaimer) {
                return $disclaimer;
            }
        }

        return null;
    }

    public function isAccepted(Disclaimer $disclaimer, ?User $user, ?string $guestIdentifier): bool
    {
        if (! $disclaimer->trigger->isPersistent()) {
            return Session::has("disclaimer_seen.{$disclaimer->id}");
        }

        if ($user) {
            return DisclaimerAcceptance::where('disclaimer_id', $disclaimer->id)->where('user_id', $user->id)->exists();
        }

        if ($guestIdentifier) {
            return DisclaimerAcceptance::where('disclaimer_id', $disclaimer->id)->where('guest_identifier', $guestIdentifier)->exists();
        }

        return false;
    }
}
