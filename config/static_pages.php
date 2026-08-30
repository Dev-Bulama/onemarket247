<?php

// Single source of truth for the platform's static content pages —
// both the storefront Blade views (resources/views/storefront/pages/*)
// and the mobile app's /api/v1/pages/* endpoints read from here, so the
// two can never drift out of sync. app('app.name') strings are resolved
// at read time by App\Http\Controllers\Api\V1\PageController /
// the Blade views themselves, not baked in here.

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

    'privacy' => [
        'title' => 'Privacy Policy',
        'sections' => [
            ['heading' => '1. Information We Collect', 'body' => 'We collect information you provide directly (account details, shipping addresses, order and payment information) and information collected automatically (device, browser, and usage data) when you use :app_name.'],
            ['heading' => '2. How We Use Information', 'body' => 'We use your information to process orders, communicate with you, operate and improve the platform, prevent fraud, and comply with legal obligations.'],
            ['heading' => '3. Sharing with Vendors', 'body' => 'When you place an order, we share the information necessary to fulfil it (such as your name, shipping address, and order contents) with the relevant vendor.'],
            ['heading' => '4. Data Security', 'body' => 'We use industry-standard safeguards to protect your information, including encrypted storage of sensitive fields and secure transmission of data.'],
            ['heading' => '5. Your Rights', 'body' => 'You may access, correct, or request deletion of your personal information from your account settings, or by contacting us directly.'],
            ['heading' => '6. Cookies', 'body' => 'We use cookies and similar technologies to keep you signed in, remember your preferences, and understand how the platform is used.'],
            ['heading' => '7. Contact', 'body' => 'Questions about this policy can be sent through our contact page.'],
        ],
    ],

    'terms' => [
        'title' => 'Terms of Service',
        'sections' => [
            ['heading' => '1. About :app_name', 'body' => ':app_name is a marketplace that connects independent vendors with customers. Vendors are independently owned and operated businesses; :app_name is not the seller of record for vendor-listed products unless stated otherwise.'],
            ['heading' => '2. Accounts', 'body' => 'You are responsible for maintaining the confidentiality of your account credentials and for all activity under your account. Notify us immediately of any unauthorized use.'],
            ['heading' => '3. Orders and Payment', 'body' => 'Placing an order is an offer to purchase, subject to acceptance and stock availability. Prices and availability are set by the selling vendor and may change without notice until an order is confirmed.'],
            ['heading' => '4. Vendor Conduct', 'body' => "Vendors must list products accurately, fulfil orders promptly, and comply with applicable law. :app_name may suspend or terminate a vendor's store for violations of these terms."],
            ['heading' => '5. Returns and Refunds', 'body' => 'Return and refund eligibility is shown at checkout and in your order details, and may vary by vendor and product category.'],
            ['heading' => '6. Limitation of Liability', 'body' => ":app_name facilitates transactions between customers and vendors and, to the fullest extent permitted by law, is not liable for indirect or consequential damages arising from a vendor's products or conduct."],
            ['heading' => '7. Changes to These Terms', 'body' => 'We may update these terms from time to time. Continued use of the platform after a change constitutes acceptance of the updated terms.'],
            ['heading' => '8. Contact', 'body' => 'Questions about these terms can be sent through our contact page.'],
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
