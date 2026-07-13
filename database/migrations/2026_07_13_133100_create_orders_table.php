<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            // public-facing, non-enumerable identifier for URLs (see
            // docs/architecture/02-database-erd.md "UUIDs"); order_number
            // stays a plain sequential human-readable reference for
            // receipts/emails, never used in a URL.
            $table->uuid('public_id')->unique();
            // Assigned right after insert from the row's own id (see
            // Order::booted()) so it's guaranteed collision-free without a
            // second locking round-trip; nullable only for the instant
            // between insert and that follow-up update within the same
            // transaction.
            $table->string('order_number', 30)->nullable()->unique();

            $table->foreignId('customer_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();

            // Shipping address is snapshotted onto the order rather than a
            // live FK to the customer's address book, so editing/deleting a
            // saved address later can never corrupt a historical order —
            // the same "snapshot, don't reference" rule this project already
            // applies to commission rules and tax/currency (see
            // docs/architecture/09-lifecycles.md "Commission Lifecycle").
            $table->string('shipping_full_name');
            $table->string('shipping_phone')->nullable();
            $table->string('shipping_address_line_1');
            $table->string('shipping_address_line_2')->nullable();
            $table->foreignId('shipping_country_id')->constrained('countries')->restrictOnDelete();
            $table->foreignId('shipping_state_id')->nullable()->constrained('states')->nullOnDelete();
            $table->foreignId('shipping_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('shipping_postal_code')->nullable();

            $table->foreignId('currency_id')->constrained()->restrictOnDelete();
            $table->integer('subtotal');
            $table->integer('discount_amount')->default(0);
            $table->integer('shipping_amount')->default(0);
            $table->integer('tax_amount')->default(0);
            $table->integer('total');
            $table->string('coupon_code')->nullable();

            $table->string('status', 30)->default('pending_payment');
            $table->timestamp('placed_at');
            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
