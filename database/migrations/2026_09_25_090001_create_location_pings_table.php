<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An insert-only history of location fixes (Priority 8 item 21) — never
 * updated in place, mirroring the ledger convention used elsewhere
 * (VendorWalletTransaction, ShipmentEvent). The admin live map reads the
 * single most recent row per user as "current location"; the full table
 * (bounded by App\Actions\Location\PruneLocationHistoryAction's retention
 * window) is what draws a user's movement trail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_pings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->float('accuracy')->nullable();
            $table->dateTime('recorded_at');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_pings');
    }
};
