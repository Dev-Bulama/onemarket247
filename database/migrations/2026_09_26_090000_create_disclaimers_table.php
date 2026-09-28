<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-configurable notice/disclaimer pop-ups (Priority 9 item 22) — see
 * App\Models\Disclaimer. More than one can be active at once as long as
 * they target different triggers; ResolveActiveDisclaimerAction always
 * picks the single most-recently-created active one for a given trigger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disclaimers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->string('trigger', 40);
            $table->boolean('requires_acceptance')->default(true);
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['trigger', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disclaimers');
    }
};
