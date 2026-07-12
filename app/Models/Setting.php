<?php

namespace App\Models;

use App\Enums\SettingType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'type', 'group'];

    protected function casts(): array
    {
        return [
            'type' => SettingType::class,
        ];
    }

    /**
     * The raw `value` column is always stored as text; this accessor decodes
     * it according to `type` so callers get a bool/int/array/string back
     * rather than every setting being a string (see docs/CODING_STANDARDS.md
     * "Status / enums").
     */
    protected function typedValue(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->type) {
                SettingType::Boolean => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
                SettingType::Integer => (int) $this->value,
                SettingType::Json => json_decode((string) $this->value, true),
                default => $this->value,
            },
        );
    }
}
