<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject')->nullable();
            $table->dateTime('last_message_at')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['vendor_id', 'user_id']);
            $table->index('last_message_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
