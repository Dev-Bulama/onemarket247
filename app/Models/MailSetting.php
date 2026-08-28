<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailSetting extends Model
{
    protected $fillable = [
        'is_active', 'mailer', 'host', 'port', 'username', 'password', 'encryption',
        'from_address', 'from_name', 'logo_url', 'primary_color', 'footer_text',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'port' => 'integer',
            'password' => 'encrypted',
        ];
    }

    /**
     * The single row every part of the app reads/writes — mirrors the
     * "singleton settings model" shape used elsewhere (e.g. Store per
     * vendor), except here there's exactly one row platform-wide.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
