<?php

use App\Actions\Commission\RecordOrderItemCommissionAction;
use App\Actions\Commission\ResolveCommissionRuleAction;
use App\Enums\CommissionScopeType;
use App\Enums\CommissionType;
use App\Models\Category;
use App\Models\CommissionRule;
use App\Models\OrderItem;
use App\Models\OrderItemCommission;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\VendorOrder;
use App\Models\VendorSubscription;
use App\Models\VendorSubscriptionPlan;

function itemFor(Product $product, int $lineTotal = 10000): OrderItem
{
    $vendorOrder = VendorOrder::factory()->create(['vendor_id' => $product->vendor_id]);

    return OrderItem::factory()->create([
        'vendor_order_id' => $vendorOrder->id,
        'product_id' => $product->id,
        'line_total' => $lineTotal,
    ]);
}

test('a product-level rule wins over category, vendor, and global rules', function () {
    $vendor = Vendor::factory()->create();
    $category = Category::factory()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);
    $product->categories()->attach($category->id, ['is_primary' => true]);

    CommissionRule::factory()->global()->create(['rate_value' => 5]);
    CommissionRule::factory()->create(['scope_type' => CommissionScopeType::Vendor, 'vendor_id' => $vendor->id, 'rate_value' => 8]);
    CommissionRule::factory()->create(['scope_type' => CommissionScopeType::Category, 'category_id' => $category->id, 'rate_value' => 12]);
    $productRule = CommissionRule::factory()->create(['scope_type' => CommissionScopeType::Product, 'product_id' => $product->id, 'rate_value' => 20]);

    $item = itemFor($product);
    $rule = app(ResolveCommissionRuleAction::class)->handle($item);

    expect($rule->id)->toBe($productRule->id);
});

test('a category-level rule wins over vendor and global rules when no product rule exists', function () {
    $vendor = Vendor::factory()->create();
    $category = Category::factory()->create();
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);
    $product->categories()->attach($category->id, ['is_primary' => true]);

    CommissionRule::factory()->global()->create(['rate_value' => 5]);
    CommissionRule::factory()->create(['scope_type' => CommissionScopeType::Vendor, 'vendor_id' => $vendor->id, 'rate_value' => 8]);
    $categoryRule = CommissionRule::factory()->create(['scope_type' => CommissionScopeType::Category, 'category_id' => $category->id, 'rate_value' => 12]);

    $item = itemFor($product);
    $rule = app(ResolveCommissionRuleAction::class)->handle($item);

    expect($rule->id)->toBe($categoryRule->id);
});

test('a vendor-scoped commission_rules row wins over the legacy vendors.commission_rate column', function () {
    $vendor = Vendor::factory()->create(['commission_rate' => 15]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    CommissionRule::factory()->global()->create(['rate_value' => 5]);
    $vendorRule = CommissionRule::factory()->create(['scope_type' => CommissionScopeType::Vendor, 'vendor_id' => $vendor->id, 'rate_value' => 8]);

    $item = itemFor($product);
    $rule = app(ResolveCommissionRuleAction::class)->handle($item);

    expect($rule->id)->toBe($vendorRule->id);
});

test('the legacy vendors.commission_rate column is used as a virtual fallback rule when no commission_rules row exists for that vendor', function () {
    $vendor = Vendor::factory()->create(['commission_rate' => 15]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    CommissionRule::factory()->global()->create(['rate_value' => 5]);

    $item = itemFor($product, 10000);
    $rule = app(ResolveCommissionRuleAction::class)->handle($item);

    expect($rule->id)->toBeNull()
        ->and($rule->rate_type)->toBe(CommissionType::Percentage)
        ->and((float) $rule->rate_value)->toBe(15.0)
        ->and($rule->computeCommission(10000))->toBe(1500);

    $commission = app(RecordOrderItemCommissionAction::class)->handle($item);
    expect($commission->commission_rule_id)->toBeNull()
        ->and($commission->commission_amount)->toBe(1500)
        ->and($commission->net_amount)->toBe(8500);
});

test('a subscription-plan commission_rate_override is used when the vendor has no commission_rate and no matching commission_rules rows', function () {
    $vendor = Vendor::factory()->create(['commission_rate' => null]);
    $plan = VendorSubscriptionPlan::factory()->create(['commission_rate_override' => 6]);
    VendorSubscription::factory()->create(['vendor_id' => $vendor->id, 'vendor_subscription_plan_id' => $plan->id]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    CommissionRule::factory()->global()->create(['rate_value' => 5]);

    $item = itemFor($product, 10000);
    $rule = app(ResolveCommissionRuleAction::class)->handle($item);

    expect($rule->id)->toBeNull()
        ->and((float) $rule->rate_value)->toBe(6.0);
});

test('the global rule is used when nothing more specific matches', function () {
    $vendor = Vendor::factory()->create(['commission_rate' => null]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);

    $globalRule = CommissionRule::factory()->global()->create(['rate_value' => 5]);

    $item = itemFor($product, 10000);
    $rule = app(ResolveCommissionRuleAction::class)->handle($item);

    expect($rule->id)->toBe($globalRule->id);
});

test('a fixed-type rule computes a flat dollar commission, capped at the gross amount', function () {
    $vendor = Vendor::factory()->create(['commission_rate' => null]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);
    CommissionRule::factory()->global()->create(['rate_type' => CommissionType::Fixed, 'rate_value' => 5]);

    $cheapItem = itemFor($product, 300);
    $commission = app(RecordOrderItemCommissionAction::class)->handle($cheapItem);

    expect($commission->commission_amount)->toBe(300)
        ->and($commission->net_amount)->toBe(0);

    $expensiveItem = itemFor($product, 10000);
    $commission2 = app(RecordOrderItemCommissionAction::class)->handle($expensiveItem);

    expect($commission2->commission_amount)->toBe(500)
        ->and($commission2->net_amount)->toBe(9500);
});

test('an order-item commission snapshot is never recomputed after the fact even if the rule changes', function () {
    $vendor = Vendor::factory()->create(['commission_rate' => null]);
    $product = Product::factory()->create(['vendor_id' => $vendor->id]);
    $rule = CommissionRule::factory()->global()->create(['rate_value' => 10]);

    $item = itemFor($product, 10000);
    $commission = app(RecordOrderItemCommissionAction::class)->handle($item);

    expect($commission->commission_amount)->toBe(1000);

    $rule->update(['rate_value' => 50]);

    expect(OrderItemCommission::find($commission->id)->commission_amount)->toBe(1000);
});
