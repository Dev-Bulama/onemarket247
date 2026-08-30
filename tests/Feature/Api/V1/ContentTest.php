<?php

use App\Models\BlogPost;
use App\Notifications\ContactMessageSubmittedNotification;
use Illuminate\Support\Facades\Notification;

test('the blog index returns only published posts', function () {
    $published = BlogPost::factory()->create(['title' => 'Published Post']);
    BlogPost::factory()->draft()->create(['title' => 'Draft Post']);

    $this->getJson('/api/v1/blog')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', $published->title);
});

test('a blog post detail includes the full body', function () {
    $post = BlogPost::factory()->create(['body' => 'The full article body.']);

    $this->getJson("/api/v1/blog/{$post->slug}")
        ->assertOk()
        ->assertJsonPath('data.body', 'The full article body.');
});

test('a draft blog post 404s via the API', function () {
    $draft = BlogPost::factory()->draft()->create();

    $this->getJson("/api/v1/blog/{$draft->slug}")->assertNotFound();
});

test('static content pages are returned as structured sections', function () {
    $response = $this->getJson('/api/v1/pages/terms')->assertOk();

    expect($response->json('data.title'))->toBe('Terms of Service')
        ->and($response->json('data.sections'))->not->toBeEmpty()
        ->and($response->json('data.sections.0.heading'))->toContain('About');
});

test('the faq endpoint returns question/answer pairs', function () {
    $response = $this->getJson('/api/v1/pages/faq')->assertOk();

    expect($response->json('data.questions'))->not->toBeEmpty()
        ->and($response->json('data.questions.0.question'))->not->toBeEmpty()
        ->and($response->json('data.questions.0.answer'))->not->toBeEmpty();
});

test('submitting the contact form via the API sends a notification', function () {
    Notification::fake();

    $this->postJson('/api/v1/contact', [
        'name' => 'Jane Shopper',
        'email' => 'jane@example.com',
        'subject' => 'Question about an order',
        'message' => 'Where is my order?',
    ])->assertOk();

    Notification::assertSentOnDemand(ContactMessageSubmittedNotification::class);
});

test('the API contact form validates required fields', function () {
    $this->postJson('/api/v1/contact', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'subject', 'message']);
});
