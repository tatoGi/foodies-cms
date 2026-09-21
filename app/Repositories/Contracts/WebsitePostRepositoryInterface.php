<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\PostTranslation;

interface WebsitePostRepositoryInterface
{
    /**
     * Finds a published PostTranslation by slug.
     * Eager loads: post.translations.blocks
     */
    public function findPublishedBySlug(string $slug): ?PostTranslation;

    /**
     * Returns up to $limit published posts in the same category,
     * excluding the given post ID.
     *
     * @return array<int, array{slug:string,title:string,excerpt:string}>
     */
    public function relatedPublished(
        int $postId,
        string $category,
        string $locale,
        string $fallbackLocale,
        int $limit = 3
    ): array;
}
