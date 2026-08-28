<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A singleton table (always exactly one row, id 1 — see MailSetting::current())
 * holding the admin-configurable SMTP transport and email branding used
 * across every outbound notification. When is_active is false, mail
 * falls back to whatever MAIL_* is set in .env — this exists so an admin
 * can configure and test real SMTP credentials without ever needing
 * server/.env access, not to replace .env as a valid configuration path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(false);
            $table->string('mailer')->default('smtp');
            $table->string('host')->nullable();
            $table->unsignedInteger('port')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->string('encryption')->nullable();
            $table->string('from_address')->nullable();
            $table->string('from_name')->nullable();
            $table->string('logo_url')->nullable();
            $table->string('primary_color', 7)->nullable();
            $table->text('footer_text')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_settings');
    }
};
