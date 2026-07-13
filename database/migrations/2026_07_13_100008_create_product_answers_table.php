<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('answered_by')->constrained('users')->cascadeOnDelete();
            $table->text('answer');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_answers');
    }
};
