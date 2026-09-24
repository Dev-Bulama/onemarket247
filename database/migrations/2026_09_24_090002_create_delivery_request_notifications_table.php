<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per eligible partner alerted about a delivery request — the
 * unique, unguessable `token` is what the partner's accept link
 * (App\Http\Controllers\Delivery\DeliveryRequestAcceptController) is
 * keyed on, letting every partner get their own link while the first
 * acceptance (see App\Actions\Delivery\AcceptDeliveryRequestAction)
 * invalidates the rest.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_request_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('delivery_partner_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->dateTime('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['delivery_request_id', 'delivery_partner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_request_notifications');
    }
};
