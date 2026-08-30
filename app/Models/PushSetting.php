<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSetting extends Model
{
    protected $fillable = ['is_active', 'app_id', 'rest_api_key'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'rest_api_key' => 'encrypted',
        ];
    }

    /**
     * The single row every part of the app reads/writes — same singleton
     * shape as App\Models\MailSetting.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
