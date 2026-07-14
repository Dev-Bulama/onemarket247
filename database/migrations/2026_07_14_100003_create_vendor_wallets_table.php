<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Balance columns are cached/derived, reconciled from
     * vendor_wallet_transactions on every mutating event — never
     * hand-edited directly — see docs/architecture/02-database-erd.md
     * "Immutable ledgers". Same cached-balance-plus-ledger shape as
     * Phase 7's warehouse_stocks/stock_movements.
     */
    public function up(): void
    {
        Schema::create('vendor_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->unique()->constrained()->restrictOnDelete();
            $table->integer('pending_balance')->default(0);
            $table->integer('available_balance')->default(0);
            $table->integer('reserved_balance')->default(0);
            $table->integer('withdrawn_balance')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_wallets');
    }
};
