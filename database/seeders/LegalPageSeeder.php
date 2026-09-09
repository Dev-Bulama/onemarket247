<?php

namespace Database\Seeders;

use App\Models\LegalPage;
use App\Support\LegalPageKeys;
use Illuminate\Database\Seeder;

/**
 * Seeds the initial Terms of Service / Privacy Policy content (migrated
 * from the old hardcoded config/static_pages.php) as admin-editable rows.
 * firstOrCreate() so re-running this seeder never overwrites an admin's
 * edits to an existing row.
 */
class LegalPageSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $page) {
            LegalPage::firstOrCreate(['key' => $page['key']], $page);
        }
    }

    /**
     * @return array<int, array{key: string, title: string, body: string}>
     */
    private function pages(): array
    {
        return [
            [
                'key' => LegalPageKeys::Terms,
                'title' => 'Terms of Service',
                'body' => $this->sectionsToHtml([
                    ['heading' => '1. About :app_name', 'body' => ':app_name is a marketplace that connects independent vendors with customers. Vendors are independently owned and operated businesses; :app_name is not the seller of record for vendor-listed products unless stated otherwise.'],
                    ['heading' => '2. Accounts', 'body' => 'You are responsible for maintaining the confidentiality of your account credentials and for all activity under your account. Notify us immediately of any unauthorized use.'],
                    ['heading' => '3. Orders and Payment', 'body' => 'Placing an order is an offer to purchase, subject to acceptance and stock availability. Prices and availability are set by the selling vendor and may change without notice until an order is confirmed.'],
                    ['heading' => '4. Vendor Conduct', 'body' => "Vendors must list products accurately, fulfil orders promptly, and comply with applicable law. :app_name may suspend or terminate a vendor's store for violations of these terms."],
                    ['heading' => '5. Returns and Refunds', 'body' => 'Return and refund eligibility is shown at checkout and in your order details, and may vary by vendor and product category.'],
                    ['heading' => '6. Limitation of Liability', 'body' => ":app_name facilitates transactions between customers and vendors and, to the fullest extent permitted by law, is not liable for indirect or consequential damages arising from a vendor's products or conduct."],
                    ['heading' => '7. Account Deletion', 'body' => 'You may permanently delete your account at any time from Account Settings in the app or website. This removes your personal information and cancels any active sessions; some records (such as completed order history) may be retained as required by law or for legitimate accounting purposes.'],
                    ['heading' => '8. Changes to These Terms', 'body' => 'We may update these terms from time to time. Continued use of the platform after a change constitutes acceptance of the updated terms.'],
                    ['heading' => '9. Contact', 'body' => 'Questions about these terms can be sent through our contact page.'],
                ]),
            ],
            [
                'key' => LegalPageKeys::Privacy,
                'title' => 'Privacy Policy',
                'body' => $this->sectionsToHtml([
                    ['heading' => '1. Information We Collect', 'body' => 'We collect information you provide directly (account details, shipping addresses, order and payment information) and information collected automatically (device, browser, and usage data) when you use :app_name.'],
                    ['heading' => '2. How We Use Information', 'body' => 'We use your information to process orders, communicate with you, operate and improve the platform, prevent fraud, and comply with legal obligations.'],
                    ['heading' => '3. Sharing with Vendors', 'body' => 'When you place an order, we share the information necessary to fulfil it (such as your name, shipping address, and order contents) with the relevant vendor.'],
                    ['heading' => '4. Data Security', 'body' => 'We use industry-standard safeguards to protect your information, including encrypted storage of sensitive fields and secure transmission of data.'],
                    ['heading' => '5. Your Rights, Including Account Deletion', 'body' => 'You may access, correct, or request deletion of your personal information at any time. To delete your account and its associated personal information, go to Account Settings in the app or website and choose "Delete Account", or contact us directly.'],
                    ['heading' => '6. Cookies', 'body' => 'We use cookies and similar technologies to keep you signed in, remember your preferences, and understand how the platform is used.'],
                    ['heading' => '7. Contact', 'body' => 'Questions about this policy can be sent through our contact page.'],
                ]),
            ],
        ];
    }

    /**
     * @param  array<int, array{heading: ?string, body: string}>  $sections
     */
    private function sectionsToHtml(array $sections): string
    {
        return collect($sections)
            ->map(fn (array $section) => ($section['heading'] ? "<h3>{$section['heading']}</h3>" : '')."<p>{$section['body']}</p>")
            ->implode('');
    }
}
