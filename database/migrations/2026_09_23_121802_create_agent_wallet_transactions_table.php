<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors vendor_wallet_transactions exactly: insert-only ledger, no
     * updated_at. vendor_order_id correlates a sale_credit/refund entry
     * back to the vendor order that produced it (the agent earns a share
     * of the platform's own commission on that order — see
     * App\Actions\Wallet\CreditAgentWalletAction); agent_settlement_id
     * correlates a withdrawal_hold/paid/reversed entry.
     */
    public function up(): void
    {
        Schema::create('agent_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_wallet_id')->constrained()->restrictOnDelete();
            $table->foreignId('vendor_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('agent_settlement_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->string('balance_bucket', 20);
            $table->integer('amount');
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['agent_wallet_id', 'vendor_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_wallet_transactions');
    }
};
