<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per user recording whether they've opted in to live location
 * sharing (Priority 8 item 21) — see App\Models\LocationConsent. Only a
 * user with is_enabled=true is ever eligible to appear on the admin live
 * map or have a ping accepted by RecordLocationPingAction, regardless of
 * what a client submits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('location_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('is_enabled')->default(false);
            $table->dateTime('enabled_at')->nullable();
            $table->dateTime('disabled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('location_consents');
    }
};
