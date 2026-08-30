<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The list-card shape, mirroring resources/views/storefront/blog/index.blade.php
 * — deliberately excludes the full `body` (see BlogPostDetailResource for that).
 */
class BlogPostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->displayExcerpt(),
            'cover_image' => $this->getFirstMediaUrl('cover') ?: null,
            'author_name' => $this->whenLoaded('author', fn () => $this->author?->name),
            'published_at' => $this->published_at,
        ];
    }
}
