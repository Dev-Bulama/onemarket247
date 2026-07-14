<?php

use App\Notifications\ContactMessageSubmittedNotification;
use Illuminate\Support\Facades\Notification;

test('the contact page loads', function () {
    $this->get('/contact')->assertOk()->assertSee('Contact Us');
});

test('submitting the contact form sends a notification and redirects back with a status', function () {
    Notification::fake();

    $response = $this->from('/contact')->post('/contact', [
        'name' => 'Jane Shopper',
        'email' => 'jane@example.com',
        'subject' => 'Question about an order',
        'message' => 'Where is my order?',
    ]);

    $response->assertRedirect('/contact')->assertSessionHas('status');
    Notification::assertSentOnDemand(ContactMessageSubmittedNotification::class);
});

test('the contact form validates required fields', function () {
    $this->post('/contact', [])->assertSessionHasErrors(['name', 'email', 'subject', 'message']);
});

test('the contact form validates the email format', function () {
    $this->post('/contact', [
        'name' => 'Jane',
        'email' => 'not-an-email',
        'subject' => 'Hi',
        'message' => 'Hello there',
    ])->assertSessionHasErrors('email');
});

test('faq, terms, and privacy pages load', function () {
    $this->get('/faq')->assertOk();
    $this->get('/terms')->assertOk();
    $this->get('/privacy-policy')->assertOk();
});

test('about-us and partnership pages load', function () {
    $this->get('/about-us')->assertOk();
    $this->get('/partnership')->assertOk();
});
