<?php

use App\Models\BlogPost;
use App\Models\LegalPage;
use App\Notifications\ContactMessageSubmittedNotification;
use App\Support\LegalPageKeys;
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

test('static content pages (about-us, partnership) are returned as structured sections', function () {
    $response = $this->getJson('/api/v1/pages/about-us')->assertOk();

    expect($response->json('data.title'))->toContain('About')
        ->and($response->json('data.sections'))->not->toBeEmpty();
});

test('terms and privacy are admin-editable pages returned as a single HTML body', function () {
    LegalPage::create(['key' => LegalPageKeys::Terms, 'title' => 'Terms of Service', 'body' => '<h3>1. About :app_name</h3><p>Some terms.</p>']);
    LegalPage::create(['key' => LegalPageKeys::Privacy, 'title' => 'Privacy Policy', 'body' => '<p>Some privacy content.</p>']);

    $terms = $this->getJson('/api/v1/pages/terms')->assertOk();
    expect($terms->json('data.title'))->toBe('Terms of Service')
        ->and($terms->json('data.body'))->toContain('1. About '.config('app.name'))
        ->and($terms->json('data.body'))->not->toContain(':app_name');

    $this->getJson('/api/v1/pages/privacy')
        ->assertOk()
        ->assertJsonPath('data.title', 'Privacy Policy');
});

test('terms/privacy 404 if the admin-editable page has not been seeded yet', function () {
    $this->getJson('/api/v1/pages/terms')->assertNotFound();
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
