<?php

namespace App\Models;

use App\Enums\BlogPostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class BlogPost extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'author_id', 'title', 'slug', 'excerpt', 'body', 'status',
        'published_at', 'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'status' => BlogPostStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }

    /**
     * scopePublished() (used by both the storefront and API blog
     * controllers) requires published_at to be set and in the past, not
     * just status = Published — the admin form lets status be chosen
     * without ever touching published_at, which silently kept every
     * "Published" post invisible on the public blog page. Auto-filling it
     * here (rather than adding a default to the form field, which
     * wouldn't apply retroactively or to any other write path) fixes it
     * at the one place every write goes through.
     */
    protected static function booted(): void
    {
        static::saving(function (self $post) {
            if ($post->status === BlogPostStatus::Published && $post->published_at === null) {
                $post->published_at = now();
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', BlogPostStatus::Published)
            ->where('published_at', '<=', now());
    }

    public function displayExcerpt(): string
    {
        return $this->excerpt ?: Str::limit(strip_tags($this->body), 160);
    }
}
