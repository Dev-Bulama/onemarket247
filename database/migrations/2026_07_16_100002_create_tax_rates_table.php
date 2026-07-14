<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tax_class_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignId('country_id')->constrained()->restrictOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('postal_code')->nullable();
            $table->decimal('rate_percent', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['country_id', 'state_id', 'city_id']);
            $table->index(['tax_class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
