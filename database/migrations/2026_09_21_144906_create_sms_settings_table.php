<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Singleton settings row for Africa's Talking SMS credentials — same
 * shape and rationale as mail_settings/push_settings: an admin enters
 * real credentials through a Filament page rather than them ever being
 * hardcoded or committed. is_active gates whether
 * App\Notifications\Channels\AfricasTalkingChannel actually attempts a
 * send at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(false);
            $table->boolean('sandbox')->default(true);
            $table->string('username')->nullable();
            $table->text('api_key')->nullable();
            $table->string('sender_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_settings');
    }
};
