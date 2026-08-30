<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Singleton settings row the mobile app reads via the public
 * GET /api/v1/bootstrap endpoint on every cold start — same shape and
 * rationale as mail_settings/push_settings: an admin controls this
 * through a Filament page rather than it being baked into the app build.
 *
 * The mobile app always calls bootstrap against its own hardcoded
 * PRODUCTION_API_URL first (the one URL that's guaranteed reachable
 * without any prior configuration) and then switches its real API calls
 * to whatever `api_base_url` that response resolves to — see
 * App\Http\Controllers\Api\V1\BootstrapController::resolveApiBaseUrl().
 * This is what lets an admin point already-installed apps at a different
 * backend (e.g. a local/staging server for testing) without a rebuild.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('active_environment')->default('production');
            $table->string('local_api_url')->nullable();
            $table->string('production_api_url')->nullable();
            // Safety default: even if active_environment is accidentally
            // left on "local", real users still get production unless an
            // admin deliberately turns this off to test.
            $table->boolean('force_production')->default(true);
            $table->string('app_name')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('splash_logo_url')->nullable();
            $table->string('min_app_version')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
