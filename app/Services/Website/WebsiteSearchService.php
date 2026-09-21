<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\Post;
use App\Models\Product;
use App\Repositories\Contracts\LanguageRepositoryInterface;

class WebsiteSearchService
{
    public function __construct(
        private readonly LanguageRepositoryInterface $languageRepository,
    ) {}

    /**
     * @return array{query:string,results:array<int,array<string,mixed>>}
     */
    public function search(string $query, ?string $requestedLocale = null): array
    {
        $query = trim($query);

        if ($query === '' || mb_strlen($query) < 2) {
            return ['query' => $query, 'results' => []];
        }

        $locale = $requestedLocale ?? $this->languageRepository->defaultLocale();
        $like = '%'.$query.'%';

        // Georgian words differ only in the last character across grammatical cases.
        // Strip the last character to produce a stem that matches multiple word forms.
        // E.g. "%სამზარეულ%" matches both "სამზარეული" and "სამზარეულო".
        $likeStem = mb_strlen($query) >= 4
            ? '%'.mb_substr($query, 0, mb_strlen($query) - 1).'%'
            : null;

        $results = collect();

        // ── Products ──────────────────────────────────────────────────────
        $products = Product::query()
            ->where('is_active', true)
            ->whereHas('translations', function ($q) use ($like, $likeStem): void {
                $q->where(function ($q2) use ($like, $likeStem): void {
                    $q2->where('title', 'like', $like)
                        ->orWhere('category', 'like', $like);

                    if ($likeStem !== null) {
                        $q2->orWhere('title', 'like', $likeStem)
                            ->orWhere('category', 'like', $likeStem);
                    }
                });
            })
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->limit(8)
            ->get();

        foreach ($products as $product) {
            $translation = $product->translations->first()
                ?? $product->translations()->first();

            if (! $translation) {
                continue;
            }

            $results->push([
                'type' => 'product',
                'title' => (string) $translation->title,
                'slug' => (string) $translation->slug,
                'url' => '/product/'.$translation->slug,
                'image' => $product->cover_image ? asset('storage/'.$product->cover_image) : null,
                'price' => $product->on_sale && $product->sale_price
                    ? (float) $product->sale_price
                    : (float) $product->price,
            ]);
        }

        // ── Posts (blog / service / project) ─────────────────────────────
        $posts = Post::query()
            ->where('published', true)
            ->whereHas('translations', function ($q) use ($locale, $like, $likeStem): void {
                $q->where('locale', $locale)
                    ->where(function ($q2) use ($like, $likeStem): void {
                        $q2->where('title', 'like', $like)
                            ->orWhere('excerpt', 'like', $like)
                            ->orWhereHas('blocks', function ($q3) use ($like, $likeStem): void {
                                $q3->whereRaw('CAST(data AS CHAR) LIKE ?', [$like]);
                                if ($likeStem !== null) {
                                    $q3->orWhereRaw('CAST(data AS CHAR) LIKE ?', [$likeStem]);
                                }
                            });

                        if ($likeStem !== null) {
                            $q2->orWhere('title', 'like', $likeStem)
                                ->orWhere('excerpt', 'like', $likeStem);
                        }
                    });
            })
            ->with(['translations' => fn ($q) => $q->where('locale', $locale)])
            ->limit(8)
            ->get();

        foreach ($posts as $post) {
            $translation = $post->translations->first();

            if (! $translation) {
                continue;
            }

            $category = (string) ($post->category ?? '');
            $url = match ($category) {
                'service' => '/service/'.$translation->slug,
                'project' => '/project/'.$translation->slug,
                default => '/blog/'.$translation->slug,
            };

            $results->push([
                'type' => $category !== '' ? $category : 'blog',
                'title' => (string) $translation->title,
                'slug' => (string) $translation->slug,
                'url' => $url,
                'image' => $post->feature_image ? asset('storage/'.$post->feature_image) : null,
                'excerpt' => $translation->excerpt ? strip_tags((string) $translation->excerpt) : null,
            ]);
        }

        return [
            'query' => $query,
            'results' => $results->values()->all(),
        ];
    }
}
