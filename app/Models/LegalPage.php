<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * The Terms of Service and Privacy Policy shown on the storefront and in
 * the mobile app — always live (no is_active gate like EmailTemplate,
 * since these pages must always show *something*), seeded once by
 * LegalPageSeeder and from then on only ever edited via the admin's
 * "Legal Pages" page. `body` is HTML from a rich text editor.
 */
class LegalPage extends Model
{
    protected $fillable = ['key', 'title', 'body'];

    public static function current(string $key): ?self
    {
        return static::query()->where('key', $key)->first();
    }
}
