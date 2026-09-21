<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsSetting extends Model
{
    protected $fillable = ['is_active', 'sandbox', 'username', 'api_key', 'sender_id'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sandbox' => 'boolean',
            'api_key' => 'encrypted',
        ];
    }

    /**
     * The single row every part of the app reads/writes — same singleton
     * shape as App\Models\MailSetting and App\Models\PushSetting.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
