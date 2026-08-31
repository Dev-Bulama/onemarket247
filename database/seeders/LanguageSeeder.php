<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        // Only English has any actual translated content behind it (see
        // lang/) — Arabic and French are seeded so the schema/admin UI has
        // real rows to show, but inactive until real translations exist;
        // an admin can flip is_active on for one from Admin → Languages
        // once they do.
        $languages = [
            ['code' => 'en', 'name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_default' => true, 'is_active' => true],
            ['code' => 'ar', 'name' => 'Arabic', 'native_name' => 'العربية', 'direction' => 'rtl', 'is_default' => false, 'is_active' => false],
            ['code' => 'fr', 'name' => 'French', 'native_name' => 'Français', 'direction' => 'ltr', 'is_default' => false, 'is_active' => false],
        ];

        foreach ($languages as $data) {
            Language::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name' => $data['name'],
                    'native_name' => $data['native_name'],
                    'direction' => $data['direction'],
                    'is_default' => $data['is_default'],
                    'is_active' => $data['is_active'],
                ],
            );
        }
    }
}
