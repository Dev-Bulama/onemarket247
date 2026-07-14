<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive: Phase 11's payments table only ever needed to represent
     * "this order is awaiting payment." Real gateway integration needs a
     * public, unguessable identifier for URLs/redirects (`reference`,
     * following the same UUID pattern as orders.public_id), a client-
     * supplied idempotency key so a retried initialize call can't create
     * two payment attempts for one request, a spot for the raw gateway
     * payload (`meta`), and enough to track a refund without a full
     * dedicated refunds table yet (see the Phase 13 completion report's
     * scope decision on this).
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->uuid('reference')->nullable()->unique()->after('id');
            $table->string('idempotency_key')->nullable()->unique()->after('gateway_reference');
            $table->json('meta')->nullable()->after('amount');
            $table->unsignedInteger('refunded_amount')->default(0)->after('meta');
            $table->timestamp('paid_at')->nullable()->after('refunded_amount');
            $table->timestamp('failed_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['reference', 'idempotency_key', 'meta', 'refunded_amount', 'paid_at', 'failed_at']);
        });
    }
};
