<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Str;

class SlugService
{
    /**
     * Generate a URL-friendly slug from a string.
     * We'll use Laravel's default behavior, but allow developers to override
     * if they want to support non-ascii characters in URLs.
     */
    public function generate(string $text, string $separator = '-'): string
    {
        return Str::slug($text, $separator);
    }

    /**
     * Ensure a slug is unique for a given model and column.
     */
    public function makeUnique(string $slug, string $model, string $column = 'slug', $ignoreId = null): string
    {
        $originalSlug = $slug;
        $count = 1;

        while ($this->exists($slug, $model, $column, $ignoreId)) {
            $slug = $originalSlug.'-'.$count++;
        }

        return $slug;
    }

    /**
     * Check if a slug already exists in the database.
     */
    protected function exists(string $slug, string $model, string $column, $ignoreId = null): bool
    {
        $query = $model::where($column, $slug);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }
}
