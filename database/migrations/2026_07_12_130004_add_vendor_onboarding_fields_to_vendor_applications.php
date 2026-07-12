<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_applications', function (Blueprint $table) {
            $table->foreignId('vendor_subscription_plan_id')->nullable()->after('vendor_id')->constrained()->nullOnDelete();
            $table->string('bank_name')->nullable()->after('social_links');
            $table->text('bank_account_name')->nullable()->after('bank_name');
            $table->text('bank_account_number')->nullable()->after('bank_account_name');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_subscription_plan_id');
            $table->dropColumn(['bank_name', 'bank_account_name', 'bank_account_number']);
        });
    }
};
