<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Singleton settings row for OneSignal push credentials — same shape and
 * rationale as mail_settings (see that migration): an admin enters real
 * credentials through a Filament page rather than them ever being
 * hardcoded or committed. is_active gates whether OneSignalChannel
 * actually attempts a send at all.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(false);
            $table->string('app_id')->nullable();
            $table->text('rest_api_key')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_settings');
    }
};
