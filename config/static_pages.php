<?php

// Single source of truth for the platform's static content pages that
// don't need admin editing — both the storefront Blade views
// (resources/views/storefront/pages/*) and the mobile app's
// /api/v1/pages/* endpoints read from here, so the two can never drift
// out of sync. app('app.name') strings are resolved at read time by
// App\Http\Controllers\Api\V1\PageController / the Blade views
// themselves, not baked in here.
//
// Terms of Service and Privacy Policy are NOT here — they're
// admin-editable (see App\Models\LegalPage / App\Filament\Resources\
// LegalPages), since legal content needs to change without a redeploy.

return [

    // Each of these two pages ends with a paragraph containing real
    // in-app links (to vendor registration / contact) — that closing
    // paragraph is deliberately kept out of this shared config and
    // hardcoded directly in the Blade view instead, since a plain-text
    // API consumer (the mobile app) has no equivalent route to link to.
    'about-us' => [
        'title' => 'About :app_name',
        'sections' => [
            [
                'heading' => null,
                'body' => ':app_name is a multi-vendor marketplace that brings together independent stores in one place, so customers can shop a wide range of products while every order is fulfilled directly by the vendor who sells it.',
            ],
            [
                'heading' => null,
                'body' => 'Each vendor manages their own store, catalog, and orders through a dedicated dashboard, while our platform handles the shared essentials: secure checkout, order tracking, and customer support across every store.',
            ],
            [
                'heading' => null,
                'body' => "Whether you're here to shop or you have products of your own to sell, we'd love to have you.",
            ],
        ],
    ],

    'partnership' => [
        'title' => 'Partner with :app_name',
        'sections' => [
            [
                'heading' => null,
                'body' => 'We work with vendors, logistics providers, and other businesses that want to reach our customer base or support how we operate.',
            ],
            [
                'heading' => null,
                'body' => 'If you run a store and want to sell here, start with our vendor registration page.',
            ],
        ],
    ],

    'faq' => [
        'title' => 'Frequently Asked Questions',
        'questions' => [
            ['question' => 'How do I place an order?', 'answer' => "Browse the shop or a store page, choose a product, and follow the checkout steps. You'll receive an order confirmation by email once your payment is confirmed."],
            ['question' => 'Can I buy from more than one vendor in a single order?', 'answer' => 'Yes. Your cart can hold items from multiple stores; at checkout we split them into separate vendor shipments while still giving you one order to track.'],
            ['question' => 'How do I become a vendor?', 'answer' => 'Apply from the vendor registration page. Most applications are reviewed within a few business days.'],
            ['question' => 'What payment methods are accepted?', 'answer' => 'Supported payment methods are shown at checkout and vary by region.'],
            ['question' => 'How do returns work?', 'answer' => 'Each order includes the applicable return window and process for its vendor. You can start a return from your order history once orders are available in your account.'],
            ['question' => 'I still need help — who do I contact?', 'answer' => "Reach out through our contact page and we'll get back to you."],
        ],
    ],

];
