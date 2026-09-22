<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            [
                'code' => 'ka',
                'country_code' => 'GE',
                'name' => 'ქართული',
                'english_name' => 'Georgian',
                'georgian_name' => 'ქართული',
                'direction' => 'ltr',
                'is_active' => true,
                'is_default' => true,
                'sort_order' => 1,
            ],
            [
                'code' => 'en',
                'country_code' => 'US',
                'name' => 'English',
                'english_name' => 'English',
                'georgian_name' => 'ინგლისური',
                'direction' => 'ltr',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
            ],
        ];

        foreach ($languages as $language) {
            Language::query()->updateOrCreate(
                ['code' => $language['code']],
                $language,
            );
        }
    }
}
