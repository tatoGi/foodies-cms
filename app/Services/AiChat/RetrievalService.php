<?php

namespace App\Services\AiChat;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RetrievalService
{
    public function __construct(private EmbeddingService $embeddings) {}

    /**
     * კითხვისთვის რელევანტური chunk-ების მოძებნა.
     *
     * მცირე/საშუალო კონტენტზე (რამდენიმე ათას chunk-მდე) PHP-ში cosine
     * similarity სავსებით საკმარისია. გაზრდისას — pgvector ან Meilisearch.
     *
     * @return Collection<int, object{title: ?string, content: string, meta: array, score: float}>
     */
    public function search(string $query): Collection
    {
        $queryVector = $this->embeddings->embed($query);

        $chunks = $this->allChunks();

        $topK = (int) config('aichat.retrieval.top_k', 6);
        $minScore = (float) config('aichat.retrieval.min_score', 0.45);

        return $chunks
            ->map(function (object $chunk) use ($queryVector) {
                $chunk->score = $this->cosine($queryVector, $chunk->embedding);

                return $chunk;
            })
            ->filter(fn (object $c) => $c->score >= $minScore)
            ->sortByDesc('score')
            ->take($topK)
            ->values();
    }

    /**
     * Top chunk-ების ტექსტად აწყობა მოდელის კონტექსტისთვის.
     */
    public function buildContext(Collection $chunks): string
    {
        if ($chunks->isEmpty()) {
            return 'კონტექსტი ვერ მოიძებნა.';
        }

        return $chunks->map(function (object $c, int $i) {
            $meta = collect($c->meta ?? [])
                ->map(fn ($v, $k) => "{$k}: {$v}")
                ->implode(', ');

            $header = '['.($i + 1).'] '.($c->title ?: 'ფრაგმენტი');
            if ($meta !== '') {
                $header .= " ({$meta})";
            }

            return $header."\n".$c->content;
        })->implode("\n\n---\n\n");
    }

    private function allChunks(): Collection
    {
        // embeddings ქეშირდება 10 წუთით, რომ ყოველ request-ზე JSON decode არ მოხდეს
        return Cache::remember('aichat:chunks', 600, function () {
            return DB::table('ai_chunks')
                ->select('title', 'content', 'meta', 'embedding')
                ->get()
                ->map(function (object $row) {
                    $row->embedding = json_decode($row->embedding, true);
                    $row->meta = $row->meta ? json_decode($row->meta, true) : [];

                    return $row;
                });
        });
    }

    /**
     * @param  float[]  $a
     * @param  float[]  $b
     */
    private function cosine(array $a, array $b): float
    {
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;

        foreach ($a as $i => $v) {
            $w = $b[$i] ?? 0.0;
            $dot += $v * $w;
            $na += $v * $v;
            $nb += $w * $w;
        }

        if ($na == 0.0 || $nb == 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($na) * sqrt($nb));
    }
}
