<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `is_verified_purchase` is a plain flag rather than an order_item_id
     * FK because order_items doesn't exist until Phase 12 — a later phase
     * migration adds the real FK and backfills this flag from it. Until
     * then every review is moderated (see ReviewStatus) rather than
     * auto-published, since purchase can't be verified yet.
     */
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->string('title')->nullable();
            $table->text('body');
            $table->string('status', 20)->default('pending');
            $table->boolean('is_verified_purchase')->default(false);
            $table->text('vendor_response')->nullable();
            $table->timestamp('vendor_responded_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->unsignedInteger('helpful_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'customer_id']);
            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
