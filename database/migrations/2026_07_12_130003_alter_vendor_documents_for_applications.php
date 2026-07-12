<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 5 discovery: documents are uploaded during the public application
 * wizard, before a Vendor record exists. vendor_id becomes nullable and a
 * new vendor_application_id links pre-approval uploads; on approval the
 * approval action backfills vendor_id and the application link becomes
 * historical. Exactly one of the two is expected to be set at any time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_documents', function (Blueprint $table) {
            $table->foreignId('vendor_application_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('vendor_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_application_id');
            $table->unsignedBigInteger('vendor_id')->nullable(false)->change();
        });
    }
};
