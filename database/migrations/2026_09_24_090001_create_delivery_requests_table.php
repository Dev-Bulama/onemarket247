<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A broadcast alert for one shipment needing a delivery partner — see
 * App\Actions\Delivery\CreateDeliveryRequestAction. One request per
 * shipment (a shipment already assigned manually, or already broadcast,
 * cannot be broadcast again).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending');
            $table->integer('delivery_fee')->default(0);
            $table->dateTime('required_by')->nullable();
            $table->text('special_instructions')->nullable();
            $table->foreignId('accepted_delivery_partner_id')->nullable()->constrained('delivery_partners')->nullOnDelete();
            $table->dateTime('accepted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_requests');
    }
};
