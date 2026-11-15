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
     * Null means a platform-wide coupon (admin-created, same as before);
     * set means a vendor's own coupon, only ever valid against a cart that
     * contains at least one of that vendor's items (see
     * App\Actions\Cart\ApplyCouponAction). Per-vendor commission/wallet
     * attribution of the discount itself stays exactly as it already is
     * for every coupon today (gross vendor_order subtotal — see
     * CompleteCheckoutAction's existing "deferred" note) — this only adds
     * who a coupon belongs to and what it validates against, not a new
     * commission-accounting change.
     */
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
        });
    }
};
