<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /**
     * Set only when Vendor::booted()'s "deleting" hook auto-archived this
     * product because its vendor was suspended (soft-deleted) — distinct
     * from a vendor archiving their own product for any other reason,
     * which must never be undone by a later vendor restore. Null for
     * every product not currently in that state.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('archived_by_vendor_suspension_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('archived_by_vendor_suspension_at');
        });
    }
};
