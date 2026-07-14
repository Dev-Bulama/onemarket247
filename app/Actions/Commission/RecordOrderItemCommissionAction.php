<?php

namespace App\Actions\Commission;

use App\Enums\CommissionType;
use App\Models\OrderItem;
use App\Models\OrderItemCommission;

/**
 * Writes the immutable per-item commission snapshot at order-item
 * creation time (checkout) — never recomputed later even if
 * commission_rules change, per docs/architecture/09-lifecycles.md
 * "Commission Lifecycle" points 1-2.
 */
class RecordOrderItemCommissionAction
{
    public function __construct(private readonly ResolveCommissionRuleAction $resolveRule) {}

    public function handle(OrderItem $item): OrderItemCommission
    {
        $rule = $this->resolveRule->handle($item);
        $gross = $item->line_total;
        $commission = $rule?->computeCommission($gross) ?? 0;

        return OrderItemCommission::create([
            'order_item_id' => $item->id,
            'commission_rule_id' => $rule?->id,
            'rate_type' => $rule?->rate_type ?? CommissionType::Percentage,
            'rate_value' => $rule?->rate_value ?? 0,
            'gross_amount' => $gross,
            'commission_amount' => $commission,
            'net_amount' => $gross - $commission,
        ]);
    }
}
