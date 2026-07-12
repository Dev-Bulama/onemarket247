<?php

namespace App\Models;

use App\Enums\LanguageDirection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'native_name', 'code', 'direction', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return [
            'direction' => LanguageDirection::class,
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $language) {
            if (! $language->is_default) {
                return;
            }

            $query = static::query();

            if ($language->exists) {
                $query->where('id', '!=', $language->id);
            }

            $query->update(['is_default' => false]);
        });
    }

    public function isRtl(): bool
    {
        return $this->direction === LanguageDirection::Rtl;
    }
}
