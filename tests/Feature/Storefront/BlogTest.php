<?php

use App\Models\BlogPost;

test('the blog index shows only published posts, not drafts', function () {
    $published = BlogPost::factory()->create(['title' => 'Published Post']);
    $draft = BlogPost::factory()->draft()->create(['title' => 'Draft Post']);

    $this->get('/blog')
        ->assertOk()
        ->assertSee($published->title)
        ->assertDontSee($draft->title);
});

test('a published post detail page loads and shows related posts', function () {
    $post = BlogPost::factory()->create(['title' => 'How to Shop Smart']);
    $other = BlogPost::factory()->create(['title' => 'Vendor Spotlight']);

    $this->get(route('blog.show', $post))
        ->assertOk()
        ->assertSee($post->title)
        ->assertSee($other->title);
});

test('a draft post detail page 404s for a public visitor', function () {
    $draft = BlogPost::factory()->draft()->create();

    $this->get(route('blog.show', $draft))->assertNotFound();
});

test('a post scheduled for the future is not visible yet', function () {
    $future = BlogPost::factory()->create(['published_at' => now()->addDay()]);

    $this->get('/blog')->assertOk()->assertDontSee($future->title);
    $this->get(route('blog.show', $future))->assertNotFound();
});

test('displayExcerpt falls back to a truncated body when no excerpt is set', function () {
    $post = BlogPost::factory()->create(['excerpt' => null, 'body' => str_repeat('word ', 100)]);

    expect(strlen($post->displayExcerpt()))->toBeLessThanOrEqual(164);
});
