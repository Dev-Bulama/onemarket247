<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records that a visitor has seen/accepted a given disclaimer (Priority 9
 * item 22's "record the user's acceptance where appropriate") — insert-
 * only, one row per disclaimer per visitor. A logged-in visitor is
 * identified by user_id; a guest by guest_identifier, which reuses the
 * same long-lived `visitor_id` cookie App\Http\Middleware\TrackSiteVisit
 * already sets for analytics, rather than inventing a second cookie.
 * once_per_session acceptances never reach this table at all (see
 * DisclaimerTrigger::isPersistent()) — they live in the PHP session only,
 * since a session-scoped dismissal is meant to be forgotten again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disclaimer_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disclaimer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('guest_identifier')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['disclaimer_id', 'user_id']);
            $table->index(['disclaimer_id', 'guest_identifier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disclaimer_acceptances');
    }
};
