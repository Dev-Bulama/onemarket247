<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Writer for the audit trail described in
 * docs/architecture/10-security-architecture.md §6 ("records every
 * sensitive admin action ... with actor, before/after diff, IP,
 * timestamp"). The App\Models\AuditLog model and its Filament resource
 * already existed as a read-only ledger + UI shell — this is the missing
 * write side. Call this directly from the specific sensitive actions
 * that should be logged (role changes, commission-rule changes, wallet/
 * refund/withdrawal decisions, settings changes, vendor approval) —
 * deliberately not a blanket model-event listener, which would log noise
 * the architecture doc doesn't ask for.
 */
class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public static function record(
        string $action,
        ?Model $auditable = null,
        ?array $before = null,
        ?array $after = null,
        ?User $actor = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $actor?->id ?? static::currentActorId(),
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'before' => $before,
            'after' => $after,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }

    /**
     * Sensitive actions happen from both the admin guard (Filament pages/
     * resources) and, for a couple of actions callable via API, no guard
     * at all if invoked from a console/queue context — check every guard
     * rather than assuming 'admin'.
     */
    private static function currentActorId(): ?int
    {
        foreach (['admin', 'vendor', 'web'] as $guard) {
            if ($id = Auth::guard($guard)->id()) {
                return $id;
            }
        }

        return null;
    }
}
