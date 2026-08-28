<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Support\Mail\EmailTemplateKeys;
use Illuminate\Database\Seeder;

/**
 * Seeds one editable row per EmailTemplateKeys constant, each is_active
 * false so nothing an admin hasn't reviewed goes out — the matching
 * notification keeps sending its hardcoded fallback copy until an admin
 * edits and activates the row in the "Email Templates" admin page.
 * firstOrCreate() so re-running this seeder never overwrites an admin's
 * edits to an existing row.
 */
class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $template) {
            EmailTemplate::firstOrCreate(['key' => $template['key']], $template);
        }
    }

    /**
     * @return array<int, array{key: string, name: string, description: string, subject: string, body: string, placeholders: array<int, string>, is_active: bool}>
     */
    private function templates(): array
    {
        return [
            [
                'key' => EmailTemplateKeys::CustomerWelcome,
                'name' => 'New customer welcome',
                'description' => 'Sent once, right after a new customer creates an account.',
                'subject' => 'Welcome to OneMarket247, {{customer_name}}!',
                'body' => "Hi {{customer_name}},\n\nThanks for creating an account with OneMarket247. You're all set to browse thousands of products from independent vendors.\n\nVisit {{shop_url}} to start shopping.",
                'placeholders' => ['customer_name', 'shop_url'],
                'is_active' => false,
            ],
            [
                'key' => EmailTemplateKeys::OrderConfirmation,
                'name' => 'Order confirmation (first & every order)',
                'description' => 'Sent once, right after checkout completes and an order is created.',
                'subject' => 'Your OneMarket247 order #{{order_number}} is confirmed',
                'body' => "Hi {{customer_name}},\n\nThanks for your order! We've received order #{{order_number}} for a total of {{order_total}}.\n\nTrack it any time at {{order_url}}. We'll let you know as soon as it ships.",
                'placeholders' => ['customer_name', 'order_number', 'order_total', 'order_url'],
                'is_active' => false,
            ],
            [
                'key' => EmailTemplateKeys::VendorApplicationApproved,
                'name' => 'Vendor application approved',
                'description' => 'Sent once, right after an admin approves a vendor application.',
                'subject' => 'Your OneMarket247 vendor application has been approved!',
                'body' => "Congratulations, {{vendor_name}}!\n\nYour application to sell as \"{{store_name}}\" on OneMarket247 has been approved. Your store is live — set a password for your vendor account below to start adding products and managing orders.",
                'placeholders' => ['vendor_name', 'store_name'],
                'is_active' => false,
            ],
            [
                'key' => EmailTemplateKeys::VendorApplicationRejected,
                'name' => 'Vendor application rejected',
                'description' => 'Sent once, right after an admin rejects a vendor application.',
                'subject' => 'Your OneMarket247 vendor application',
                'body' => "Hello {{applicant_name}},\n\nThank you for applying to sell on OneMarket247. After review, we're unable to approve your application for \"{{store_name}}\" at this time.\n\nReason: {{rejection_reason}}\n\nYou're welcome to submit a new application once the issue above has been addressed.",
                'placeholders' => ['applicant_name', 'store_name', 'rejection_reason'],
                'is_active' => false,
            ],
            [
                'key' => EmailTemplateKeys::MarketingSample,
                'name' => 'Marketing announcement (sample)',
                'description' => 'A starting point for one-off announcements sent via the "Send Message" admin page — not wired to any automatic trigger.',
                'subject' => 'A special offer just for you, {{customer_name}}',
                'body' => "Hi {{customer_name}},\n\nWe've got something special going on at OneMarket247 and wanted you to be the first to know. Check it out at {{shop_url}}.\n\nAs always, thanks for shopping with us.",
                'placeholders' => ['customer_name', 'shop_url'],
                'is_active' => false,
            ],
        ];
    }
}
