<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Arabic and French were seeded as active/switchable, but there is no
 * actual translated UI content behind either (see lang/ — only en/ has
 * ever been populated), so the storefront's language switcher offered a
 * choice that visibly did nothing. Deactivating them here (rather than
 * removing the switcher itself) keeps the mechanism intact — an admin
 * can flip a language back on from Admin → Languages once real
 * translations exist for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('languages')->whereNotIn('code', ['en'])->update(['is_active' => false]);
    }

    public function down(): void
    {
        // Not reversible — we don't know which languages were active
        // before this ran.
    }
};
