<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_variation_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('on_hand')->default(0);
            $table->unsignedInteger('reserved')->default(0);
            $table->unsignedInteger('damaged')->default(0);
            $table->unsignedInteger('incoming')->default(0);
            $table->timestamps();

            // No DB-level uniqueness here: product_id/product_variation_id are
            // mutually exclusive and MySQL/SQLite treat NULL as distinct in
            // unique indexes, so a (warehouse_id, product_id, NULL) tuple
            // would not actually be deduplicated. Uniqueness is instead
            // guaranteed by always writing through App\Actions\Inventory's
            // lockForUpdate()+firstOrCreate() pattern.
            $table->index(['product_id', 'warehouse_id']);
            $table->index(['product_variation_id', 'warehouse_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_stocks');
    }
};
