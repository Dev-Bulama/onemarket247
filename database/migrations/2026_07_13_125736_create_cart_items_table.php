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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            // Refreshed to the current price on every add/quantity update, so
            // a comparison against the live product/variation price at read
            // time can only ever detect drift that happened *after* the
            // customer last touched this line — never a stale add-time price.
            $table->integer('unit_price');
            $table->boolean('saved_for_later')->default(false);
            $table->timestamps();

            $table->unique(['cart_id', 'product_id', 'product_variation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
