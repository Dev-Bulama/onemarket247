<?php

namespace App\Models;

use App\Enums\AttributeInputType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'attribute_group_id', 'name', 'slug', 'input_type', 'is_filterable', 'is_variation', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'input_type' => AttributeInputType::class,
            'is_filterable' => 'boolean',
            'is_variation' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(AttributeGroup::class, 'attribute_group_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class);
    }
}
