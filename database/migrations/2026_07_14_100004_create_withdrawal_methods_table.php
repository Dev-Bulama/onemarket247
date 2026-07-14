<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * account_name/account_number are encrypted casts — same convention
     * as vendors.bank_account_name/bank_account_number from Phase 2/5.
     */
    public function up(): void
    {
        Schema::create('withdrawal_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->restrictOnDelete();
            $table->string('type', 30)->default('bank_transfer');
            $table->string('bank_name');
            $table->text('account_name');
            $table->text('account_number');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdrawal_methods');
    }
};
