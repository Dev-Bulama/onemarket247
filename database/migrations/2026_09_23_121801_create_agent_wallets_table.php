<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors vendor_wallets exactly (see that migration's docblock):
     * balance columns are cached/derived, reconciled from
     * agent_wallet_transactions on every mutating event — never
     * hand-edited directly.
     */
    public function up(): void
    {
        Schema::create('agent_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->unique()->constrained()->restrictOnDelete();
            $table->integer('pending_balance')->default(0);
            $table->integer('available_balance')->default(0);
            $table->integer('reserved_balance')->default(0);
            $table->integer('withdrawn_balance')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_wallets');
    }
};
