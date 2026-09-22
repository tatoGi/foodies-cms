<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GeneralSetting;
use App\Repositories\Contracts\LanguageRepositoryInterface;
use Illuminate\Support\Facades\Storage;

class GeneralSettingService
{
    public function __construct(
        private readonly LanguageRepositoryInterface $languageRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildEditViewData(): array
    {
        $locales = $this->languageRepository->getActiveLocales();
        $defaultLocale = $this->languageRepository->defaultLocale();

        $settings = GeneralSetting::query()
            ->whereIn('key', [
                'header_logo', 'footer_logo', 'breadcrumb_image', 'breadcrumb_color', 'footer_contact',
                'social_links', 'contact_phone', 'contact_email', 'contact_address',
            ])
            ->get()
            ->keyBy('key');

        return [
            'locales' => $locales,
            'defaultLocale' => $defaultLocale,
            'selectedLocaleCodes' => collect($locales)->pluck('code')->values()->all(),
            'headerLogo' => is_string($settings->get('header_logo')?->value) ? $settings->get('header_logo')->value : null,
            'footerLogo' => is_string($settings->get('footer_logo')?->value) ? $settings->get('footer_logo')->value : null,
            'breadcrumbImage' => is_string($settings->get('breadcrumb_image')?->value) ? $settings->get('breadcrumb_image')->value : null,
            'breadcrumbColor' => $this->color($settings->get('breadcrumb_color')?->value),
            'footerContact' => (array) ($settings->get('footer_contact')?->value ?? []),
            'socialLinks' => (array) ($settings->get('social_links')?->value ?? []),
            'contactPhone' => (array) ($settings->get('contact_phone')?->value ?? []),
            'contactEmail' => (array) ($settings->get('contact_email')?->value ?? []),
            'contactAddress' => (array) ($settings->get('contact_address')?->value ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public function save(array $validated): void
    {
        $this->upsert('header_logo', trim($validated['header_logo'] ?? ''), 'identity', 'Header Logo');
        $this->upsert('footer_logo', trim($validated['footer_logo'] ?? ''), 'identity', 'Footer Logo');
        $this->upsert('breadcrumb_image', trim($validated['breadcrumb_image'] ?? ''), 'identity', 'Breadcrumb Banner');
        $this->upsert('breadcrumb_color', $this->color($validated['breadcrumb_color'] ?? null), 'identity', 'Breadcrumb Color');

        $footerContact = collect((array) ($validated['footer_contact'] ?? []))
            ->map(static fn ($v): string => trim((string) $v))
            ->filter(static fn ($v): bool => $v !== '')
            ->all();
        $this->upsert('footer_contact', $footerContact !== [] ? $footerContact : [], 'footer', 'Footer Contact Text');

        // Social links (not localised — single URL per platform)
        $socialLinks = collect((array) ($validated['social_links'] ?? []))
            ->map(static fn ($v): string => trim((string) $v))
            ->filter(static fn ($v): bool => $v !== '')
            ->all();
        $this->upsert('social_links', $socialLinks, 'social', 'Social Media Links');

        // Bilingual contact fields
        foreach (['contact_phone' => 'Contact Phone', 'contact_email' => 'Contact Email', 'contact_address' => 'Contact Address'] as $key => $label) {
            $value = collect((array) ($validated[$key] ?? []))
                ->map(static fn ($v): string => trim((string) $v))
                ->filter(static fn ($v): bool => $v !== '')
                ->all();
            $this->upsert($key, $value, 'contact', $label);
        }
    }

    public function getFrontendSettings(?string $locale = null, ?string $defaultLocale = null): array
    {
        $settings = GeneralSetting::query()
            ->whereIn('key', [
                'header_logo', 'footer_logo', 'breadcrumb_image', 'breadcrumb_color', 'footer_contact',
                'social_links', 'contact_phone', 'contact_email', 'contact_address',
            ])
            ->get()
            ->keyBy('key');

        $resolvedLocale = strtolower(trim((string) $locale));
        $resolvedDefaultLocale = strtolower(trim((string) $defaultLocale));

        $footerContactByLocale = (array) ($settings->get('footer_contact')?->value ?? []);
        $footerContactText = $this->resolveLocalizedText($footerContactByLocale, $resolvedLocale, $resolvedDefaultLocale);

        return [
            'headerLogo' => $settings->get('header_logo')?->value,
            'footerLogo' => $settings->get('footer_logo')?->value,
            'breadcrumbImage' => $this->assetUrl($settings->get('breadcrumb_image')?->value),
            'breadcrumbColor' => $this->color($settings->get('breadcrumb_color')?->value),
            'footerContactText' => $footerContactText !== '' ? $footerContactText : null,
            'footerContactByLocale' => $footerContactByLocale !== [] ? $footerContactByLocale : null,
            'socialLinks' => (array) ($settings->get('social_links')?->value ?? []),
            'contactPhone' => $this->resolveLocalizedText((array) ($settings->get('contact_phone')?->value ?? []), $resolvedLocale, $resolvedDefaultLocale) ?: null,
            'contactEmail' => $this->resolveLocalizedText((array) ($settings->get('contact_email')?->value ?? []), $resolvedLocale, $resolvedDefaultLocale) ?: null,
            'contactAddress' => $this->resolveLocalizedText((array) ($settings->get('contact_address')?->value ?? []), $resolvedLocale, $resolvedDefaultLocale) ?: null,
        ];
    }

    private function resolveLocalizedText(array $byLocale, string $locale, string $defaultLocale): string
    {
        if ($locale !== '' && isset($byLocale[$locale]) && trim((string) $byLocale[$locale]) !== '') {
            return trim((string) $byLocale[$locale]);
        }
        if ($defaultLocale !== '' && isset($byLocale[$defaultLocale]) && trim((string) $byLocale[$defaultLocale]) !== '') {
            return trim((string) $byLocale[$defaultLocale]);
        }

        return collect($byLocale)
            ->map(static fn ($v): string => trim((string) $v))
            ->first(static fn (string $v): bool => $v !== '') ?? '';
    }

    private function color(mixed $value): string
    {
        $color = strtolower(trim((string) $value));

        return preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/', $color) === 1 ? $color : '#1c1714';
    }

    private function assetUrl(mixed $path): ?string
    {
        $value = trim((string) $path);
        if ($value === '') {
            return null;
        }
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://') || str_starts_with($value, '/')) {
            return $value;
        }

        return Storage::disk('public')->url($value);
    }

    private function upsert(string $key, mixed $value, string $group, string $label): void
    {
        GeneralSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group, 'label' => $label, 'is_public' => true],
        );
    }
}
