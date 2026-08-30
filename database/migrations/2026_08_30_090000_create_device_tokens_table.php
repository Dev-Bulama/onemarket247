<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per (user, device) — a OneSignal "player id" the mobile app
 * registered after the user granted push permission (see
 * POST /api/v1/device-tokens). A user can hold several rows (phone +
 * tablet, or a reinstall that got a new player id); `token` is unique so
 * re-registering the same device just updates which user it now belongs
 * to rather than creating a duplicate row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('platform')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
