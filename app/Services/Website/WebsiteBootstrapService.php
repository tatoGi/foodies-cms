<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Repositories\Contracts\LanguageRepositoryInterface;
use App\Repositories\Contracts\WebsiteMenuRepositoryInterface;
use App\Repositories\Contracts\WebsitePageRepositoryInterface;
use App\Services\GeneralSettingService;

class WebsiteBootstrapService
{
    public function __construct(
        private readonly WebsiteMenuRepositoryInterface $menuRepo,
        private readonly LanguageRepositoryInterface $langRepo,
        private readonly WebsitePageRepositoryInterface $pageRepo,
        private readonly GeneralSettingService $settingService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function bootstrap(?string $requestedLocale = null): array
    {
        $activeLanguages = $this->langRepo->getActiveLocales();
        $defaultLocale = strtolower($this->langRepo->defaultLocale());
        $locale = $this->resolveLocale($requestedLocale, $activeLanguages, $defaultLocale);

        return [
            'locale' => $locale,
            'defaultLocale' => $defaultLocale,
            'languages' => $activeLanguages,
            'navigation' => [
                'header' => $this->menuRepo->resolvedItems('header', $locale, $defaultLocale),
                'footer' => $this->menuRepo->resolvedItems('footer', $locale, $defaultLocale),
            ],
            'settings' => $this->settingService->getFrontendSettings($locale, $defaultLocale),
            'routeMap' => $this->pageRepo->publishedPageSlugs($locale, $defaultLocale),
        ];
    }

    /**
     * @param  array<int, array{code:string}>  $activeLanguages
     */
    private function resolveLocale(?string $requestedLocale, array $activeLanguages, string $defaultLocale): string
    {
        $requested = strtolower(trim((string) $requestedLocale));

        if ($requested === '') {
            return $defaultLocale;
        }

        foreach ($activeLanguages as $lang) {
            if (strtolower($lang['code']) === $requested) {
                return $requested;
            }
        }

        return $defaultLocale;
    }
}
