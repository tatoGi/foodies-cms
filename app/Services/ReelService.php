<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Reel;
use App\Models\ReelTranslation;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReelService
{
    public function __construct(
        private readonly LanguageRepositoryInterface $languageRepository,
    ) {}

    /**
     * @return array{reels: LengthAwarePaginator, productReels: Collection<int, Product>, currentLocale: string}
     */
    public function buildIndexViewData(): array
    {
        return [
            'reels' => Reel::query()
                ->with('translations')
                ->orderBy('sort_order')
                ->orderByDesc('created_at')
                ->paginate(20),
            'productReels' => Product::query()
                ->where('show_in_reels', true)
                ->with('translations')
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->get(),
            'currentLocale' => app()->getLocale(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildCreateViewData(): array
    {
        $locales = $this->languageRepository->getActiveLocales();
        $defaultLocale = $this->languageRepository->defaultLocale();
        $allLocaleCodes = collect($locales)
            ->pluck('code')
            ->map(static fn ($code): string => (string) $code)
            ->values()
            ->all();
        $selectedLocaleCodes = $this->resolveSelectedLocaleCodes($locales, $allLocaleCodes);

        return [
            'locales' => $locales,
            'defaultLocale' => $defaultLocale,
            'selectedLocaleCodes' => $selectedLocaleCodes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildEditViewData(Reel $reel): array
    {
        $reel->load('translations');

        $locales = $this->languageRepository->getActiveLocales();
        $defaultLocale = $this->languageRepository->defaultLocale();

        $fallbackSelected = collect($locales)
            ->pluck('code')
            ->map(static fn ($code): string => (string) $code)
            ->values()
            ->all();
        $selectedLocaleCodes = $this->resolveSelectedLocaleCodes($locales, $fallbackSelected);

        return [
            'reel' => $reel,
            'locales' => $locales,
            'defaultLocale' => $defaultLocale,
            'selectedLocaleCodes' => $selectedLocaleCodes,
            'translations' => $reel->translations->keyBy('locale'),
        ];
    }

    public function create(array $validated): Reel
    {
        return DB::transaction(function () use ($validated): Reel {
            $reel = Reel::create([
                'thumbnail_url' => null,
                'video_url' => $validated['video_url'] ?? null,
                'category' => $validated['category'],
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $this->syncTranslations($reel, $validated);

            return $reel;
        });
    }

    public function update(Reel $reel, array $validated): void
    {
        DB::transaction(function () use ($reel, $validated): void {
            $reel->update([
                'video_url' => $validated['video_url'] ?? null,
                'category' => $validated['category'],
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_active' => (bool) ($validated['is_active'] ?? false),
            ]);

            $this->syncTranslations($reel, $validated);
        });
    }

    public function delete(Reel $reel): void
    {
        $reel->delete();
    }

    /**
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $position => $id) {
            Reel::query()->where('id', $id)->update(['sort_order' => $position]);
        }
    }

    private function syncTranslations(Reel $reel, array $validated): void
    {
        $titles = (array) ($validated['titles'] ?? []);
        $slugs = (array) ($validated['slugs'] ?? []);
        $descriptions = (array) ($validated['descriptions'] ?? []);

        $submittedLocales = array_unique(array_merge(
            array_keys($titles),
            array_keys($slugs),
            array_keys($descriptions),
        ));

        foreach ($submittedLocales as $locale) {
            $title = trim((string) ($titles[$locale] ?? ''));
            $slug = trim((string) ($slugs[$locale] ?? ''));
            $description = trim((string) ($descriptions[$locale] ?? ''));

            if ($title === '' && $slug === '' && $description === '') {
                ReelTranslation::query()
                    ->where('reel_id', $reel->id)
                    ->where('locale', $locale)
                    ->delete();

                continue;
            }

            ReelTranslation::query()->updateOrCreate(
                ['reel_id' => $reel->id, 'locale' => $locale],
                ['title' => $title, 'slug' => $slug, 'description' => $description],
            );
        }
    }

    /**
     * @param  array<int, array{code:string,name:string,flag:string,is_default:bool}>  $locales
     * @param  array<int, string>  $fallback
     * @return array<int, string>
     */
    private function resolveSelectedLocaleCodes(array $locales, array $fallback): array
    {
        $allCodes = collect($locales)
            ->pluck('code')
            ->map(static fn ($code): string => (string) $code)
            ->values()
            ->all();

        $fromOld = collect((array) old('titles', []))
            ->keys()
            ->map(static fn ($code): string => (string) $code)
            ->filter(static fn (string $code): bool => in_array($code, $allCodes, true))
            ->values()
            ->all();

        $selected = $fromOld !== [] ? $fromOld : $fallback;

        return collect($selected)
            ->filter(static fn (string $code): bool => in_array($code, $allCodes, true))
            ->values()
            ->all();
    }
}
