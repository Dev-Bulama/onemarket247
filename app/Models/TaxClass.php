<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxClass extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'is_default', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $class) {
            if (! $class->is_default) {
                return;
            }

            $query = static::query();

            if ($class->exists) {
                $query->where('id', '!=', $class->id);
            }

            $query->update(['is_default' => false]);
        });
    }

    public function rates(): HasMany
    {
        return $this->hasMany(TaxRate::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
