<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Creates the site's header menu from data/navigation.json (exported from the site).
 * A menu that already has items is left alone, so admin edits are never overwritten.
 */
class SiteNavigationDemoSeeder extends Seeder
{
    private const MENU_TITLES = ['header' => 'Header'];

    public function run(): void
    {
        /** @var array<string, list<array{url: string, labels: array<string, string>}>> $menus */
        $menus = json_decode(File::get(database_path('seeders/data/navigation.json')), true, flags: JSON_THROW_ON_ERROR);

        foreach ($menus as $slug => $items) {
            $menu = Menu::query()->firstOrCreate(
                ['slug' => $slug],
                ['title' => self::MENU_TITLES[$slug] ?? ucfirst($slug), 'is_active' => true],
            );
            if ($menu->items()->exists()) {
                continue;
            }

            DB::transaction(function () use ($menu, $items): void {
                foreach ($items as $order => $item) {
                    $row = $menu->items()->create([
                        'parent_id' => null,
                        'is_active' => true,
                        'order' => $order + 1,
                        'type' => 'custom',
                        'url' => $item['url'],
                        'target' => '_self',
                    ]);
                    foreach ($item['labels'] as $locale => $label) {
                        $row->translations()->create(['locale' => $locale, 'label' => $label, 'slug' => '']);
                    }
                }
            });
        }
    }
}
