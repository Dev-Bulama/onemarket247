<?php

namespace Database\Factories;

use App\Enums\BlogPostStatus;
use App\Models\BlogPost;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BlogPost>
 */
class BlogPostFactory extends Factory
{
    protected $model = BlogPost::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(6);

        return [
            'author_id' => null,
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->sentence(),
            'body' => fake()->paragraphs(5, true),
            'status' => BlogPostStatus::Published,
            'published_at' => now(),
            'seo_title' => null,
            'seo_description' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => BlogPostStatus::Draft, 'published_at' => null]);
    }
}
