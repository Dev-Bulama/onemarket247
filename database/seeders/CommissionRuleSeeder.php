<?php

namespace Database\Seeders;

use App\Enums\CommissionScopeType;
use App\Enums\CommissionType;
use App\Models\CommissionRule;
use Illuminate\Database\Seeder;

/**
 * Every commission resolution falls back to this rule if nothing more
 * specific matches (see App\Actions\Commission\ResolveCommissionRuleAction)
 * — without it, a vendor/category/product with no explicit rule would
 * honestly earn 0% commission rather than the platform default.
 */
class CommissionRuleSeeder extends Seeder
{
    public function run(): void
    {
        if (CommissionRule::where('scope_type', CommissionScopeType::Global)->exists()) {
            return;
        }

        CommissionRule::create([
            'scope_type' => CommissionScopeType::Global,
            'rate_type' => CommissionType::Percentage,
            'rate_value' => 10,
            'is_active' => true,
        ]);
    }
}
