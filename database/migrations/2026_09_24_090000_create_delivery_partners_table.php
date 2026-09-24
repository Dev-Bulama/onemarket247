<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The registered courier/rider roster — see App\Models\DeliveryPartner.
 * Mirrors agents' shape (a directory entity, not a login-capable account):
 * a partner interacts with the system entirely through unguessable-link
 * pages (App\Http\Controllers\Delivery\*), never a password-protected
 * account, per the Priority 7 scope decision to avoid a new login system.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_partners', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('vehicle_type')->nullable();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_partners');
    }
};
