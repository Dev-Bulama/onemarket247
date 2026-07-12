<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Vendor data-isolation layer 1 (see docs/architecture/01-system-architecture.md
 * §4): auto-filters queries on vendor-owned tables to the acting vendor
 * whenever the current actor authenticated through the "vendor" guard, so a
 * missing ->where() elsewhere cannot leak another vendor's data. Inert for
 * every other guard (admin panel queries are never scoped by this).
 */
class BelongsToVendorScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::guard('vendor')->check()) {
            return;
        }

        $vendorId = Auth::guard('vendor')->user()->actingVendorId();

        $builder->where($model->getTable().'.vendor_id', $vendorId);
    }
}
