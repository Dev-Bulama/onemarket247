<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per notification "key" (see App\Support\Mail\EmailTemplateKeys) —
 * an admin-editable subject/body an admin can override; is_active=false
 * (the seeded default) means the notification keeps using its hardcoded
 * fallback copy, so nothing changes for anyone until an admin actually
 * edits and activates a template.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('subject');
            $table->text('body');
            $table->json('placeholders')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
