<?php

use App\Models\LegalPage;
use App\Support\LegalPageKeys;
use Database\Seeders\LegalPageSeeder;

test('the storefront terms and privacy pages render the admin-edited content', function () {
    (new LegalPageSeeder)->run();
    LegalPage::where('key', LegalPageKeys::Terms)->update(['body' => '<h3>Custom Heading</h3><p>Custom terms body.</p>']);

    $this->get(route('pages.terms'))
        ->assertOk()
        ->assertSee('Custom Heading', false)
        ->assertSee('Custom terms body.', false);

    $this->get(route('pages.privacy'))->assertOk();
});

test('the storefront terms page 404s if it has not been seeded yet', function () {
    $this->get(route('pages.terms'))->assertNotFound();
});
