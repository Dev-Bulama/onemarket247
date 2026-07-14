<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Category extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'parent_id', 'name', 'slug', 'description', 'path',
        'is_active', 'sort_order', 'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $category) {
            if ($category->isDirty('parent_id')) {
                $category->unsetRelation('parent');
            }

            $category->path = $category->parent
                ? trim(($category->parent->path ?? '').'/'.$category->parent->id, '/')
                : null;
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    /**
     * This category's id plus every descendant's id, using the
     * materialized `path` column (an ancestor-id chain — see the `saving`
     * hook above) so a category page can include products filed under any
     * of its subcategories without a recursive query.
     *
     * @return Collection<int, int>
     */
    public function descendantIds(): Collection
    {
        $descendantIds = static::query()
            ->where('path', $this->id)
            ->orWhere('path', 'like', "{$this->id}/%")
            ->orWhere('path', 'like', "%/{$this->id}")
            ->orWhere('path', 'like', "%/{$this->id}/%")
            ->pluck('id');

        return $descendantIds->push($this->id);
    }
}
