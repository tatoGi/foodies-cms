<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\GeneralSetting;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\Contracts\WebsiteMenuRepositoryInterface;
use App\Repositories\Contracts\WebsitePageRepositoryInterface;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class NavigationService
{
    public function __construct(
        private readonly WebsiteMenuRepositoryInterface $menuRepo,
        private readonly WebsitePageRepositoryInterface $pageRepo,
        private readonly LanguageRepositoryInterface $langRepo,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function payload(?string $requestedLocale = null): array
    {
        [$locale, $defaultLocale, $activeLanguages] = $this->resolveLocaleContext($requestedLocale);
        $settings = $this->settingsByKeys(['header_logo', 'footer_logo', 'footer_contact']);
        $contactByLocale = $this->localeMapValue($settings, 'footer_contact');
        $headerMenuItems = $this->menuRepo->resolvedItems('header', $locale, $defaultLocale);
        $footerMenuItems = $this->menuRepo->resolvedItems('footer', $locale, $defaultLocale);

        return [
            'locale' => $locale,
            'defaultLocale' => $defaultLocale,
            'activeLanguages' => $activeLanguages,
            'headerLogo' => $this->toAssetUrl($this->stringValue($settings, 'header_logo')),
            'footerLogo' => $this->toAssetUrl($this->stringValue($settings, 'footer_logo')),
            'footerContactText' => $this->resolveLocalizedText($contactByLocale, $locale, $defaultLocale),
            'footerContactByLocale' => $contactByLocale,
            'headerMenuItems' => $headerMenuItems,
            'footerMenuItems' => $footerMenuItems,
            'pages' => $this->pageRepo->publishedPageSlugs($locale, $defaultLocale),
        ];
    }

    /**
     * @return array{0:string,1:string,2:array<int,array{code:string,name:string,flag:string,is_default:bool}>}
     */
    private function resolveLocaleContext(?string $requestedLocale): array
    {
        $activeLanguages = $this->langRepo->getActiveLocales();
        $defaultLocale = strtolower($this->langRepo->defaultLocale());
        $locale = strtolower(trim((string) $requestedLocale));

        if ($locale === '') {
            return [$defaultLocale, $defaultLocale, $activeLanguages];
        }

        $activeCodes = collect($activeLanguages)
            ->pluck('code')
            ->map(static fn ($code): string => strtolower((string) $code))
            ->values()
            ->all();

        if (! in_array($locale, $activeCodes, true)) {
            return [$defaultLocale, $defaultLocale, $activeLanguages];
        }

        return [$locale, $defaultLocale, $activeLanguages];
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    private function settingsByKeys(array $keys): array
    {
        if (! Schema::hasTable('general_settings')) {
            return [];
        }

        return GeneralSetting::query()
            ->whereIn('key', $keys)
            ->get(['key', 'value'])
            ->mapWithKeys(static fn (GeneralSetting $setting): array => [
                (string) $setting->key => $setting->value,
            ])
            ->all();
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function stringValue(array $settings, string $key): string
    {
        return trim((string) ($settings[$key] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, string>
     */
    private function localeMapValue(array $settings, string $key): array
    {
        $value = $settings[$key] ?? [];
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->mapWithKeys(static function (mixed $text, mixed $locale): array {
                $localeCode = strtolower(trim((string) $locale));
                $resolvedText = trim((string) $text);

                if ($localeCode === '' || $resolvedText === '') {
                    return [];
                }

                return [$localeCode => $resolvedText];
            })
            ->all();
    }

    /**
     * @param  array<string, string>  $contactByLocale
     */
    private function resolveLocalizedText(array $contactByLocale, string $locale, string $defaultLocale): string
    {
        $primary = trim((string) ($contactByLocale[$locale] ?? ''));
        if ($primary !== '') {
            return $primary;
        }

        $fallback = trim((string) ($contactByLocale[strtolower($defaultLocale)] ?? ''));
        if ($fallback !== '') {
            return $fallback;
        }

        return collect($contactByLocale)
            ->map(static fn (string $text): string => trim($text))
            ->first(static fn (string $text): bool => $text !== '') ?? '';
    }

    private function toAssetUrl(string $path): ?string
    {
        if ($path === '') {
            return null;
        }

        if (
            str_starts_with($path, '/') ||
            str_starts_with($path, 'http://') ||
            str_starts_with($path, 'https://')
        ) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
