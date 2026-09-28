<?php

namespace App\Actions\Disclaimer;

use App\Models\Disclaimer;
use App\Models\DisclaimerAcceptance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

/**
 * Records that a visitor has seen/accepted a disclaimer — see
 * ResolveActiveDisclaimerAction for how that's later checked.
 * once_per_session never reaches the database (mirrors that action's own
 * session-vs-persistent split); everything else writes one
 * DisclaimerAcceptance row per disclaimer per visitor, never duplicated.
 */
class RecordDisclaimerAcceptanceAction
{
    public function handle(Disclaimer $disclaimer, ?User $user, ?string $guestIdentifier, Request $request): void
    {
        if (! $disclaimer->trigger->isPersistent()) {
            Session::put("disclaimer_seen.{$disclaimer->id}", true);

            return;
        }

        if ($user) {
            DisclaimerAcceptance::firstOrCreate(
                ['disclaimer_id' => $disclaimer->id, 'user_id' => $user->id],
                ['ip_address' => $request->ip(), 'user_agent' => $request->userAgent()],
            );

            return;
        }

        if (! $guestIdentifier) {
            return;
        }

        $alreadyRecorded = DisclaimerAcceptance::where('disclaimer_id', $disclaimer->id)
            ->where('guest_identifier', $guestIdentifier)
            ->exists();

        if (! $alreadyRecorded) {
            DisclaimerAcceptance::create([
                'disclaimer_id' => $disclaimer->id,
                'guest_identifier' => $guestIdentifier,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }
    }
}
