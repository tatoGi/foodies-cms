<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MenuItem;
use App\Models\Page;
use App\Models\PageTranslation;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use Illuminate\Support\Facades\Schema;

class MenuPageSlugSyncService
{
    public function __construct(
        private readonly LanguageRepositoryInterface $languageRepository,
    ) {}

    public function syncMenuItemsFromPage(int $pageId): void
    {
        if (! $this->tablesAvailable()) {
            return;
        }

        $this->attachPageToParentMenus($pageId);

        $defaultLocale = strtolower($this->languageRepository->defaultLocale());
        $page = Page::query()
            ->with('translations')
            ->find($pageId);

        if (! $page instanceof Page) {
            return;
        }

        $slugByLocale = $this->slugByLocale($page);
        $titleByLocale = $this->titleByLocale($page);
        $defaultSlug = $this->defaultSlug($slugByLocale, $defaultLocale);
        $resolvedUrl = $defaultSlug !== '' ? '/'.$defaultSlug : null;

        $menuItems = MenuItem::query()
            ->where('type', 'page')
            ->where('reference_id', $pageId)
            ->with('translations')
            ->get();

        foreach ($menuItems as $menuItem) {
            if ($menuItem->url !== $resolvedUrl) {
                $menuItem->update(['url' => $resolvedUrl]);
            }

            $existingByLocale = $menuItem->translations
                ->keyBy(static fn ($translation): string => strtolower((string) $translation->locale));

            foreach ($slugByLocale as $locale => $slug) {
                $existing = $existingByLocale->get($locale);
                $label = trim((string) ($existing?->label ?? $titleByLocale[$locale] ?? $titleByLocale[$defaultLocale] ?? ''));
                if ($label === '') {
                    $label = $slug;
                }

                $menuItem->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'label' => $label,
                        'slug' => $slug,
                    ]
                );
            }

            if ($defaultSlug !== '') {
                foreach ($existingByLocale as $locale => $translation) {
                    if (isset($slugByLocale[$locale])) {
                        continue;
                    }

                    $translation->update(['slug' => $defaultSlug]);
                }
            }
        }
    }

    public function attachPageToParentMenus(int $pageId): void
    {
        if (! $this->tablesAvailable()) {
            return;
        }

        $page = Page::query()
            ->with('translations')
            ->find($pageId);

        if (! $page instanceof Page || $page->parent_id === null) {
            return;
        }

        $parentMenuItems = MenuItem::query()
            ->where('type', 'page')
            ->where('reference_id', (int) $page->parent_id)
            ->get();

        if ($parentMenuItems->isEmpty()) {
            return;
        }

        $defaultLocale = strtolower($this->languageRepository->defaultLocale());
        $slugByLocale = $this->slugByLocale($page);
        $titleByLocale = $this->titleByLocale($page);
        $defaultSlug = $this->defaultSlug($slugByLocale, $defaultLocale);
        $resolvedUrl = $defaultSlug !== '' ? '/'.$defaultSlug : null;

        foreach ($parentMenuItems as $parentItem) {
            $alreadyAttached = MenuItem::query()
                ->where('menu_id', $parentItem->menu_id)
                ->where('parent_id', $parentItem->id)
                ->where('type', 'page')
                ->where('reference_id', $pageId)
                ->exists();

            if ($alreadyAttached) {
                continue;
            }

            $maxOrder = (int) MenuItem::query()
                ->where('menu_id', $parentItem->menu_id)
                ->where('parent_id', $parentItem->id)
                ->max('order');

            $newItem = MenuItem::query()->create([
                'menu_id' => (int) $parentItem->menu_id,
                'parent_id' => (int) $parentItem->id,
                'type' => 'page',
                'reference_id' => $pageId,
                'url' => $resolvedUrl,
                'target' => '_self',
                'order' => $maxOrder + 1,
            ]);

            foreach ($slugByLocale as $locale => $slug) {
                $label = trim((string) ($titleByLocale[$locale] ?? $titleByLocale[$defaultLocale] ?? ''));
                if ($label === '') {
                    $label = $slug;
                }

                $newItem->translations()->updateOrCreate(
                    ['locale' => $locale],
                    [
                        'label' => $label,
                        'slug' => $slug,
                    ]
                );
            }
        }
    }

    private function tablesAvailable(): bool
    {
        return Schema::hasTable('pages')
            && Schema::hasTable('page_translations')
            && Schema::hasTable('menu_items')
            && Schema::hasTable('menu_item_translations');
    }

    /**
     * @return array<string, string>
     */
    private function slugByLocale(Page $page): array
    {
        return $page->translations
            ->mapWithKeys(static function (PageTranslation $translation): array {
                $locale = strtolower(trim((string) $translation->locale));
                $slug = trim((string) $translation->slug);

                if ($locale === '' || $slug === '') {
                    return [];
                }

                return [$locale => $slug];
            })
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function titleByLocale(Page $page): array
    {
        return $page->translations
            ->mapWithKeys(static function (PageTranslation $translation): array {
                $locale = strtolower(trim((string) $translation->locale));
                $title = trim((string) $translation->title);

                if ($locale === '' || $title === '') {
                    return [];
                }

                return [$locale => $title];
            })
            ->all();
    }

    /**
     * @param  array<string, string>  $slugByLocale
     */
    private function defaultSlug(array $slugByLocale, string $defaultLocale): string
    {
        $defaultSlug = trim((string) ($slugByLocale[$defaultLocale] ?? ''));
        if ($defaultSlug !== '') {
            return $defaultSlug;
        }

        return trim((string) (collect($slugByLocale)->first() ?? ''));
    }
}
