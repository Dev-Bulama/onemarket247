<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Baseline platform settings. Additional keys are added by the phase that
 * introduces the feature they configure (e.g. vendor approval mode ships
 * with Phase 5, payment gateway toggles with Phase 13).
 */
class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'app.name', 'value' => 'OneMarket247', 'type' => 'string', 'group' => 'general'],
            ['key' => 'app.default_currency', 'value' => 'NGN', 'type' => 'string', 'group' => 'general'],
            ['key' => 'app.default_language', 'value' => 'en', 'type' => 'string', 'group' => 'general'],
            ['key' => 'vendor.approval_mode', 'value' => 'manual', 'type' => 'string', 'group' => 'vendor'],
            ['key' => 'vendor.require_document_verification', 'value' => '1', 'type' => 'boolean', 'group' => 'vendor'],
            ['key' => 'products.approval_mode', 'value' => 'manual', 'type' => 'string', 'group' => 'products'],
            ['key' => 'finance.minimum_withdrawal', 'value' => '5000', 'type' => 'integer', 'group' => 'finance'],
            ['key' => 'storefront.announcement_text', 'value' => 'Free delivery on orders ₦15,000+ in Lagos.', 'type' => 'string', 'group' => 'storefront'],
        ];

        foreach ($settings as $data) {
            Setting::updateOrCreate(['key' => $data['key']], $data);
        }
    }
}
