<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_applications', function (Blueprint $table) {
            $table->string('agent_id_number')->nullable()->after('tax_identification_number');
            $table->string('agent_full_name')->nullable()->after('agent_id_number');
            $table->string('agent_phone')->nullable()->after('agent_full_name');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_applications', function (Blueprint $table) {
            $table->dropColumn(['agent_id_number', 'agent_full_name', 'agent_phone']);
        });
    }
};
