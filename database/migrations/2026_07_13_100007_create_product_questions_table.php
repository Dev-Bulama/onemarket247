<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->text('question');
            $table->boolean('is_answered')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['product_id', 'is_answered']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_questions');
    }
};
