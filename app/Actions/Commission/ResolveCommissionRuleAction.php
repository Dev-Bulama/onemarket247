<?php

namespace App\Actions\Commission;

use App\Enums\CommissionScopeType;
use App\Enums\CommissionType;
use App\Models\CommissionRule;
use App\Models\OrderItem;

/**
 * Resolution order (most to least specific) per
 * docs/architecture/09-lifecycles.md "Commission Lifecycle": product >
 * category > vendor > subscription-plan > global. Returns null if truly
 * no rule matches anywhere (including no global default configured yet),
 * which RecordOrderItemCommissionAction treats as "no commission charged"
 * rather than blocking checkout on missing configuration.
 *
 * The vendor and subscription-plan tiers each have two possible sources:
 * an explicit commission_rules row (scope_type Vendor/SubscriptionPlan),
 * checked first, or the single-value override column that already existed
 * on vendors.commission_rate/vendor_subscription_plans.commission_rate_override
 * since Phases 2/5 (built then as forward-looking placeholders for this
 * exact phase, already wired into their own Filament forms and — for
 * subscription plans — already seeded with real data). Rather than
 * duplicate that as a second, disconnected representation of "this
 * vendor's/plan's commission rate," this action treats the legacy column
 * as that tier's fallback when no commission_rules row exists for it, via
 * an unpersisted "virtual" CommissionRule carrying the override value.
 */
class ResolveCommissionRuleAction
{
    public function handle(OrderItem $item): ?CommissionRule
    {
        $productRule = CommissionRule::where('scope_type', CommissionScopeType::Product)
            ->where('product_id', $item->product_id)
            ->where('is_active', true)
            ->first();

        if ($productRule) {
            return $productRule;
        }

        $categoryIds = $item->product->categories()->pluck('categories.id');

        if ($categoryIds->isNotEmpty()) {
            $categoryRule = CommissionRule::where('scope_type', CommissionScopeType::Category)
                ->whereIn('category_id', $categoryIds)
                ->where('is_active', true)
                ->first();

            if ($categoryRule) {
                return $categoryRule;
            }
        }

        $vendor = $item->vendorOrder->vendor;

        $vendorRule = CommissionRule::where('scope_type', CommissionScopeType::Vendor)
            ->where('vendor_id', $vendor->id)
            ->where('is_active', true)
            ->first();

        if ($vendorRule) {
            return $vendorRule;
        }

        if ($vendor->commission_rate !== null) {
            return $this->virtualRule(CommissionScopeType::Vendor, $vendor->commission_rate);
        }

        $plan = $vendor->currentSubscription()?->plan;

        if ($plan) {
            $planRule = CommissionRule::where('scope_type', CommissionScopeType::SubscriptionPlan)
                ->where('subscription_plan_id', $plan->id)
                ->where('is_active', true)
                ->first();

            if ($planRule) {
                return $planRule;
            }

            if ($plan->commission_rate_override !== null) {
                return $this->virtualRule(CommissionScopeType::SubscriptionPlan, $plan->commission_rate_override);
            }
        }

        return CommissionRule::where('scope_type', CommissionScopeType::Global)
            ->where('is_active', true)
            ->first();
    }

    /**
     * An in-memory, never-persisted rule representing a legacy single-value
     * override column — see the class docblock. commission_rule_id stays
     * null on the resulting snapshot, same as "no rule found at all,"
     * since there's no real commission_rules row to reference.
     */
    private function virtualRule(CommissionScopeType $scopeType, string|float $rateValue): CommissionRule
    {
        return new CommissionRule([
            'scope_type' => $scopeType,
            'rate_type' => CommissionType::Percentage,
            'rate_value' => $rateValue,
        ]);
    }
}
