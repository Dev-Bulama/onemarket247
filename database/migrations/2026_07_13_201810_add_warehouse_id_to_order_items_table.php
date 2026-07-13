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
        Schema::table('order_items', function (Blueprint $table) {
            // Phase 11 selected a warehouse to reserve stock from at
            // checkout time but had nothing yet that needed to remember
            // which one; Phase 12's cancellation flow needs to release
            // that exact reservation, hence this additive column.
            $table->foreignId('warehouse_id')->nullable()->after('product_variation_id')
                ->constrained()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('warehouse_id');
        });
    }
};
