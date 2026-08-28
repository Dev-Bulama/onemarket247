<?php

test('submitting the contact form succeeds even when the mail transport is unreachable', function () {
    config([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => '127.0.0.1',
        'mail.mailers.smtp.port' => 1,
    ]);

    $response = $this->post(route('pages.contact.submit'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'subject' => 'A question',
        'message' => 'Does this ship to Lagos?',
    ]);

    $response->assertRedirect()->assertSessionHas('status');
});
