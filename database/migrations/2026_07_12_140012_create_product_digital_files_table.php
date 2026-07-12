<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deliberately no product_downloads (entitlement/download-count) table this
 * phase — an entitlement is created from a purchased order_item, and that
 * table doesn't exist until Phase 12. This table only stores the protected
 * file itself; download access control until then is vendor/admin-only
 * (see App\Http\Controllers\ProductDigitalFileDownloadController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_digital_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('file_path');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_digital_files');
    }
};
