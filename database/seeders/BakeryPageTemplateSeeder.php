<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PageTemplate;
use Illuminate\Database\Seeder;

class BakeryPageTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'home' => ['ka' => 'მთავარი', 'en' => 'Home'],
            'inner' => ['ka' => 'შიდა გვერდი', 'en' => 'Inner page'],
        ];

        foreach ($templates as $slug => $names) {
            $template = PageTemplate::query()->firstOrCreate(['slug' => $slug]);

            foreach ($names as $locale => $name) {
                $template->translations()->updateOrCreate(
                    ['locale' => $locale],
                    ['name' => $name]
                );
            }
        }
    }
}
