<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Insert-only audit trail of every gateway interaction (initialize
     * request/response, verify request/response, webhook payloads,
     * signature-verification failures) — see docs/architecture/
     * 10-security-architecture.md "Payment Security". No updated_at, by
     * the same "immutable ledger" convention as audit_logs.
     * payment_id is nullable because a webhook that fails to resolve to
     * any known payment (bad reference, replay of something never
     * initialized here) must still be logged for investigation.
     */
    public function up(): void
    {
        Schema::create('payment_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway', 50);
            $table->string('direction', 20);
            $table->json('payload');
            $table->timestamp('created_at')->nullable();

            $table->index(['payment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_logs');
    }
};
