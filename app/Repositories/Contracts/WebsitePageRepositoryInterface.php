<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Page;
use App\Models\PageTranslation;

interface WebsitePageRepositoryInterface
{
    /**
     * Finds a published PageTranslation by slug.
     * Eager loads: page.translations.blocks, page.posts.translations
     */
    public function findPublishedBySlug(string $slug): ?PageTranslation;

    /**
     * Returns published pages as slug-oriented navigation entries.
     *
     * @return array<int, array{id:int,slug:string,title:string,template:string,url:string,api_url:string}>
     */
    public function publishedPageSlugs(string $locale, string $fallbackLocale): array;

    /**
     * Resolves the homepage Page by template priority:
     * ['home','homepage','main','index'] → is_home=true → slug match.
     * Eager loads: translations.blocks, parent.translations, children.translations,
     * posts.translations.blocks, products.translations.blocks
     */
    public function findHomepage(string $locale, string $fallbackLocale): ?Page;

    /**
     * Finds a published page by template slug/name.
     * Eager loads: translations.blocks, parent.translations, children.translations,
     * posts.translations.blocks, products.translations.blocks
     */
    public function findPublishedByTemplate(string $template, string $locale, string $fallbackLocale): ?Page;

    /**
     * Returns the 3 most recent published posts, formatted for frontend.
     *
     * @return array<int, array{slug:string,title:string,excerpt:string,published_at:string|null}>
     */
    public function recentPublishedPosts(string $locale, string $fallbackLocale, int $limit = 3): array;
}
