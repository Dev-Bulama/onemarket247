<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_applications', function (Blueprint $table) {
            $table->foreignId('agent_id')->nullable()->after('agent_phone')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('vendor_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agent_id');
        });
    }
};
