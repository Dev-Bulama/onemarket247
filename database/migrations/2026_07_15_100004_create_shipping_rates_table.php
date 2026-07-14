<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_zone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipping_class_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('rate_type', 20)->default('flat');
            $table->unsignedInteger('base_amount')->default(0);
            $table->unsignedInteger('per_kg_amount')->nullable();
            $table->unsignedInteger('free_shipping_min_amount')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['shipping_zone_id', 'shipping_class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rates');
    }
};
