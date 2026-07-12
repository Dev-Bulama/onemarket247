<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('user_type', 20)->default('customer')->after('email');
            $table->string('status', 20)->default('active')->after('user_type');
            $table->string('phone')->nullable()->unique()->after('status');
            $table->timestamp('phone_verified_at')->nullable()->after('phone');

            $table->index('user_type');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['user_type']);
            $table->dropIndex(['status']);
            $table->dropUnique(['phone']);
            $table->dropColumn(['user_type', 'status', 'phone', 'phone_verified_at']);
        });
    }
};
