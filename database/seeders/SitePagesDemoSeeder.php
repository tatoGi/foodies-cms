<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageTranslation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Creates the site's pages with demo content exported from the site (data/site-pages.json).
 * A page whose slug already exists is skipped, so admin edits are never overwritten.
 */
class SitePagesDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->copyDemoImages();

        /** @var array<string, array{template: string, slugs: array<string, string>, titles: array<string, string>, block_types?: list<string>, blocks: list<array{type: string, data: array<string, array<string, mixed>>}>}> $pages */
        $pages = json_decode(File::get(database_path('seeders/data/site-pages.json')), true, flags: JSON_THROW_ON_ERROR);

        foreach ($pages as $definition) {
            if (PageTranslation::query()->whereIn('slug', array_values($definition['slugs']))->exists()) {
                continue;
            }

            DB::transaction(function () use ($definition): void {
                $page = Page::query()->create([
                    'template' => $definition['template'],
                    'block_types' => $definition['block_types'] ?? array_column($definition['blocks'], 'type'),
                    'published' => true,
                    'show_in_menu' => false,
                    'is_home' => false,
                    'sort_order' => 0,
                ]);

                foreach ($definition['slugs'] as $locale => $slug) {
                    $translation = $page->translations()->create([
                        'locale' => $locale,
                        'title' => $definition['titles'][$locale],
                        'slug' => $slug,
                        'published_at' => now(),
                    ]);

                    foreach ($definition['blocks'] as $index => $block) {
                        $translation->blocks()->create([
                            'type' => $block['type'],
                            'data' => $block['data'][$locale],
                            'sort_order' => ($index + 1) * 10,
                        ]);
                    }
                }
            });
        }
    }

    private function copyDemoImages(): void
    {
        $source = database_path('seeders/demo-images');
        if (! File::isDirectory($source)) {
            return;
        }

        foreach (File::allFiles($source) as $file) {
            $target = 'demo/'.str_replace('\\', '/', $file->getRelativePathname());
            if (! Storage::disk('public')->exists($target)) {
                Storage::disk('public')->put($target, $file->getContents());
            }
        }
    }
}
