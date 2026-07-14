<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Insert-only ledger — no updated_at, same convention as audit_logs/
     * payment_logs/stock_movements. `amount` is a signed delta applied to
     * whichever `balance_bucket` it names; vendor_order_id correlates a
     * sale_credit/refund entry back to the order that produced it (so a
     * later refund can tell whether that credit is still pending or has
     * already settled to available — see
     * docs/architecture/09-lifecycles.md "Vendor Wallet Lifecycle").
     * withdrawal_id correlates a withdrawal_hold/paid/reversed entry.
     */
    public function up(): void
    {
        Schema::create('vendor_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_wallet_id')->constrained()->restrictOnDelete();
            $table->foreignId('vendor_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('withdrawal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->string('balance_bucket', 20);
            $table->integer('amount');
            $table->text('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();

            $table->index(['vendor_wallet_id', 'vendor_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_wallet_transactions');
    }
};
