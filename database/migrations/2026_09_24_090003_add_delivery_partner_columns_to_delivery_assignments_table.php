<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links a delivery assignment back to the partner who accepted it and the
 * request that produced it, and gives it the unguessable `tracking_token`
 * a partner uses to update their own progress with no login — see
 * App\Actions\Delivery\AcceptDeliveryRequestAction and
 * App\Http\Controllers\Delivery\DeliveryTrackingController. Both FKs stay
 * nullable: an assignment created the old way, via
 * App\Actions\Shipping\AssignDeliveryAction with a plain name/phone, has
 * neither.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_assignments', function (Blueprint $table) {
            $table->foreignId('delivery_partner_id')->nullable()->after('shipment_id')->constrained()->nullOnDelete();
            $table->foreignId('delivery_request_id')->nullable()->after('delivery_partner_id')->constrained()->nullOnDelete();
            $table->string('tracking_token', 64)->nullable()->unique()->after('delivery_request_id');
        });
    }

    public function down(): void
    {
        Schema::table('delivery_assignments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delivery_partner_id');
            $table->dropConstrainedForeignId('delivery_request_id');
            $table->dropColumn('tracking_token');
        });
    }
};
