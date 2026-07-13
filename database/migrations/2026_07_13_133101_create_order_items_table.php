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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variation_id')->nullable()->constrained()->restrictOnDelete();

            // Product name/SKU are snapshotted so a later rename (or, once
            // products are soft-deletable in practice, a deletion) can never
            // change what a historical receipt says was purchased.
            $table->string('product_name');
            $table->string('sku');
            $table->integer('unit_price');
            $table->unsignedInteger('quantity');
            $table->integer('line_total');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
