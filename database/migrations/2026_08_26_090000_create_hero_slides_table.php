<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('image_path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Carry forward whatever photo an admin already uploaded through the
        // old single-photo hero page, so this migration doesn't blank out a
        // live homepage.
        if (Storage::disk('public')->exists('hero/slide-1.jpg')) {
            DB::table('hero_slides')->insert([
                'image_path' => 'hero/slide-1.jpg',
                'sort_order' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hero_slides');
    }
};
