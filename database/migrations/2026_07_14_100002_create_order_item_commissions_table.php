<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Immutable snapshot written at order-item creation time (checkout),
     * never recomputed if commission_rules change later — see
     * docs/architecture/09-lifecycles.md "Commission Lifecycle" points 1-2.
     * commission_rule_id is nullOnDelete (not restrict) because deleting a
     * rule must never be blocked by historical orders that already
     * snapshotted its rate — the snapshot columns (rate_type/rate_value)
     * are what make this row meaningful on their own.
     */
    public function up(): void
    {
        Schema::create('order_item_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('commission_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rate_type', 20);
            $table->decimal('rate_value', 8, 2);
            $table->integer('gross_amount');
            $table->integer('commission_amount');
            $table->integer('net_amount');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_commissions');
    }
};
