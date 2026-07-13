<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * A guest order (no customer_id) has no account to check against — the
 * unguessable public_id UUID in the URL is itself the credential, matching
 * the reasoning in docs/architecture/02-database-erd.md for using UUIDs as
 * a public-facing identifier. A registered customer's order is only
 * viewable by that same customer (while authenticated) or an admin with
 * orders.view — a different logged-in customer must never see it even if
 * they somehow obtain the link.
 */
class OrderPolicy
{
    public function view(?User $user, Order $order): bool
    {
        if ($order->customer_id === null) {
            return true;
        }

        return $user !== null && ($order->customer_id === $user->id || $user->can('orders.view'));
    }
}
