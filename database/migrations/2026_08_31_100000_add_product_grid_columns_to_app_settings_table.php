<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How many product cards per row the storefront and mobile app grids use —
 * same bootstrap-read, no-rebuild-needed pattern as the rest of
 * app_settings (see the create-table migration's docblock).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('product_grid_columns')->default(4);
        });
    }

    public function down(): void
    {
        Schema::table('app_settings', function (Blueprint $table) {
            $table->dropColumn('product_grid_columns');
        });
    }
};
