<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_evidence', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_assignment_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('file_path');
            $table->string('recipient_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_evidence');
    }
};
