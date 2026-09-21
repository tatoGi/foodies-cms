<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

interface WebsiteMenuRepositoryInterface
{
    /**
     * Returns resolved navigation items for the given menu slug.
     * Handles locale fallback, reference ID → slug mapping,
     * and URL normalization.
     *
     * @return array<int, array{label:string,url:string,target:string,type:string,slug:?string,api_url:?string,children:array}>
     */
    public function resolvedItems(
        string $menuSlug,
        string $locale,
        string $fallbackLocale
    ): array;
}
