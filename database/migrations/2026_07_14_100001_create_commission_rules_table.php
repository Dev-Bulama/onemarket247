<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * scope_type disambiguates which (nullable) FK, if any, narrows this
     * rule; exactly one of category_id/product_id/vendor_id/
     * subscription_plan_id is set when scope_type names that scope, and
     * all four are null for a 'global' rule. Resolution order is by
     * specificity — product > category > vendor > subscription_plan >
     * global — per docs/architecture/09-lifecycles.md "Commission
     * Lifecycle".
     */
    public function up(): void
    {
        Schema::create('commission_rules', function (Blueprint $table) {
            $table->id();
            $table->string('scope_type', 30);
            $table->foreignId('category_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->nullable()->constrained('vendor_subscription_plans')->cascadeOnDelete();
            $table->string('rate_type', 20);
            $table->decimal('rate_value', 8, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('scope_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rules');
    }
};
