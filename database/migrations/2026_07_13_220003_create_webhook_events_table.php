<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The dedupe mechanism for "duplicate callbacks can't duplicate
     * payments" (Phase 13 gate): the unique (gateway, event_id) pair is
     * inserted inside the same transaction that processes a webhook, so a
     * replayed/duplicated delivery hits a unique-constraint violation and
     * is acknowledged with zero additional side effects rather than
     * reprocessed. See docs/architecture/10-security-architecture.md
     * "Payment Security".
     */
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 50);
            $table->string('event_id');
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
