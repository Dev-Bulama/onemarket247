<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Immutable, insert-only ledger (see docs/architecture/02-database-erd.md
     * "Immutable ledgers") — never updated or deleted; on_hand/reserved/
     * damaged/incoming on warehouse_stocks are the derived cache, this
     * table is the source of truth.
     */
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_stock_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('bucket', 20);
            $table->integer('quantity_delta');
            $table->string('reason')->nullable();
            $table->nullableMorphs('reference');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['warehouse_stock_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
