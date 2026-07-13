<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->morphs('votable');
            $table->boolean('is_helpful')->default(true);
            $table->timestamps();

            $table->unique(['customer_id', 'votable_type', 'votable_id'], 'review_votes_customer_votable_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_votes');
    }
};
