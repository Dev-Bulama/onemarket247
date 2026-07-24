<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('flash_sale_starts_at')->nullable()->after('compare_at_price');
            $table->timestamp('flash_sale_ends_at')->nullable()->after('flash_sale_starts_at');

            $table->index(['flash_sale_starts_at', 'flash_sale_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['flash_sale_starts_at', 'flash_sale_ends_at']);
            $table->dropColumn(['flash_sale_starts_at', 'flash_sale_ends_at']);
        });
    }
};
