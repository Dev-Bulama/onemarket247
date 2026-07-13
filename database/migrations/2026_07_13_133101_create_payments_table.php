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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            // Deliberately minimal — no gateway config, webhook, or log
            // columns yet. Real gateway integration (Paystack), signature
            // verification, and reconciliation are Phase 13's job (see
            // docs/architecture/09-lifecycles.md "Payment Lifecycle"); this
            // phase only needs a row that honestly represents "this order
            // is awaiting payment" to exist the moment checkout completes.
            $table->string('gateway', 50)->nullable();
            $table->string('gateway_reference')->nullable()->unique();
            $table->string('status', 20)->default('pending');
            $table->integer('amount');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
