<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_default' => true],
            ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'is_default' => false],
            ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'direction' => 'ltr', 'is_default' => false],
        ];

        foreach ($languages as $data) {
            Language::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'native_name' => $data['native_name'],
                    'direction' => $data['direction'],
                    'is_default' => $data['is_default'],
                    'is_active' => true,
                ],
            );
        }
    }
}
