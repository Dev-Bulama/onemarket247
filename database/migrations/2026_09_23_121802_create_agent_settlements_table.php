<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors withdrawals, with one deliberate difference: an Agent has no
     * account of their own to request a payout from (see App\Models\Agent's
     * docblock) — a settlement is always created by an admin on the
     * agent's behalf, hence created_by instead of a vendor-style
     * self-service request. reference follows the human-readable
     * "AS-{year}-{id}" convention (see VendorApplication/AgentApplication)
     * rather than withdrawals' bare UUID.
     */
    public function up(): void
    {
        Schema::create('agent_settlements', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();
            $table->foreignId('agent_id')->constrained()->restrictOnDelete();
            $table->integer('amount');
            $table->string('status', 20)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['agent_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_settlements');
    }
};
