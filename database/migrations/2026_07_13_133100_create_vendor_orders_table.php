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
        Schema::create('vendor_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->string('vendor_order_number', 30)->nullable()->unique();

            $table->integer('subtotal');
            $table->integer('discount_amount')->default(0);
            $table->integer('shipping_amount')->default(0);
            $table->integer('tax_amount')->default(0);
            $table->integer('total');

            $table->string('status', 30)->default('pending_payment');
            $table->timestamps();

            $table->index(['vendor_id', 'status']);
            $table->index(['order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_orders');
    }
};
