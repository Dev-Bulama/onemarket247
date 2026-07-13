<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            // Minimal MVP shape: a flat percentage-or-fixed discount with an
            // optional minimum spend and validity window. Per-vendor scoping,
            // stacking rules, flash-sale exclusivity, per-customer usage
            // limits, and automatic/tiered discount_rules are the full
            // Promotions module and are deferred to Phase 17 (see
            // 02-database-erd.md's "Promotions" batch and Phase 17's
            // completion gate) — nothing here blocks that later migration
            // from adding columns.
            $table->string('type', 20);
            $table->unsignedInteger('value');
            $table->integer('minimum_spend')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
