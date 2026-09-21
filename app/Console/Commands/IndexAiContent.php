<?php

namespace App\Console\Commands;

use App\Services\AiChat\EmbeddingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IndexAiContent extends Command
{
    protected $signature = 'aichat:index {--fresh : ყველაფრის თავიდან ინდექსაცია}';

    protected $description = 'CMS კონტენტისა და პროდუქტების ინდექსაცია AI ჩატისთვის';

    public function handle(EmbeddingService $embeddings): int
    {
        if ($this->option('fresh')) {
            DB::table('ai_chunks')->truncate();
            $this->info('ძველი ინდექსი წაიშალა.');
        }

        $chunkSize = (int) config('aichat.retrieval.chunk_size');
        $overlap = (int) config('aichat.retrieval.chunk_overlap');

        foreach (config('aichat.sources') as $sourceType => $cfg) {
            if (! class_exists($cfg['model'])) {
                $this->warn("{$sourceType}: მოდელი {$cfg['model']} ვერ მოიძებნა — გამოტოვებულია. მოარგე config/aichat.php");

                continue;
            }

            $query = $cfg['model']::query();
            if (! empty($cfg['scope']) && method_exists($cfg['model'], 'scope'.Str::studly($cfg['scope']))) {
                $query->{$cfg['scope']}();
            }

            // მშობელი მოდელის ფილტრები (published/is_active და ა.შ.)
            foreach ($cfg['where_has'] ?? [] as $relation => $conditions) {
                $query->whereHas($relation, fn ($q) => $q->where($conditions));
            }

            $count = 0;

            $query->chunkById(100, function ($records) use (
                $sourceType, $cfg, $chunkSize, $overlap, $embeddings, &$count
            ) {
                foreach ($records as $record) {
                    // ტექსტის აწყობა მითითებული ველებიდან (HTML იწმინდება,
                    // JSON/array მნიშვნელობები — მაგ. ბლოკის data — ტექსტად იშლება)
                    $text = collect($cfg['fields'])
                        ->map(fn (string $f) => $this->stringify(data_get($record, $f)))
                        ->filter()
                        ->implode("\n\n");

                    if ($text === '') {
                        continue;
                    }

                    // dot-notation მშობელ relation-ზე (product.price → price)
                    $meta = collect($cfg['meta'] ?? [])
                        ->mapWithKeys(fn (string $f) => [Str::afterLast($f, '.') => data_get($record, $f)])
                        ->filter(fn ($v) => $v !== null && $v !== '')
                        ->all();

                    if (! empty($cfg['url'])) {
                        $meta['url'] = preg_replace_callback(
                            '/\{([\w.]+)\}/',
                            fn ($m) => (string) (data_get($record, $m[1]) ?? ''),
                            $cfg['url']
                        );
                    }

                    // პროდუქტისთვის ფასი ტექსტშივეც ჩაიწეროს, რომ მოდელმა ნახოს
                    if (isset($meta['price'])) {
                        $text .= "\n\nფასი: {$meta['price']} ₾";
                    }

                    $titleValue = isset($cfg['title']) ? data_get($record, $cfg['title']) : null;
                    $titleValue ??= $record->name ?? $record->title ?? "#{$record->id}";

                    $title = ($cfg['label'] ?? $sourceType).': '.$titleValue;

                    foreach ($this->splitText($text, $chunkSize, $overlap) as $piece) {
                        $hash = hash('sha256', $sourceType.$record->id.$piece);

                        // უცვლელი კონტენტი ხელახლა არ embed-დება
                        $exists = DB::table('ai_chunks')->where('content_hash', $hash)->exists();
                        if ($exists) {
                            continue;
                        }

                        DB::table('ai_chunks')
                            ->where('source_type', $sourceType)
                            ->where('source_id', $record->id)
                            ->where('content_hash', '!=', $hash)
                            ->delete();

                        [$vector] = $embeddings->embedBatch([$title."\n".$piece]);

                        DB::table('ai_chunks')->insert([
                            'source_type' => $sourceType,
                            'source_id' => $record->id,
                            'title' => mb_substr($title, 0, 250),
                            'content' => $piece,
                            'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                            'embedding' => json_encode($vector),
                            'content_hash' => $hash,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $count++;
                        usleep(150_000); // უფასო tier-ის rate limit-ის დასაცავად
                    }
                }
            });

            $this->info("{$sourceType}: {$count} ახალი chunk დაინდექსდა.");
        }

        Cache::forget('aichat:chunks');
        $this->info('ინდექსაცია დასრულდა. ქეში განახლდა.');

        return self::SUCCESS;
    }

    /**
     * მნიშვნელობის ტექსტად ქცევა: სტრიქონები იწმინდება HTML-ისგან,
     * მასივები (ბლოკის JSON data) რეკურსიულად იშლება, მედია-ფაილების
     * გზები და ტექნიკური მნიშვნელობები გამოიტოვება.
     */
    private function stringify(mixed $value): string
    {
        if (is_array($value)) {
            return collect($value)
                ->map(fn ($v) => $this->stringify($v))
                ->filter()
                ->implode("\n");
        }

        if (is_bool($value) || ! is_scalar($value)) {
            return '';
        }

        $value = trim(strip_tags((string) $value));

        // ფაილის გზები (სურათები, ვიდეო...) ტექსტში არ შედის
        if (preg_match('/\.(jpe?g|png|webp|gif|svg|avif|mp4|webm|pdf)$/i', $value)) {
            return '';
        }

        return $value;
    }

    /** @return string[] */
    private function splitText(string $text, int $size, int $overlap): array
    {
        if (mb_strlen($text) <= $size) {
            return [$text];
        }

        $pieces = [];
        $start = 0;
        $length = mb_strlen($text);

        while ($start < $length) {
            $piece = mb_substr($text, $start, $size);

            // შეძლებისდაგვარად წინადადების ბოლოზე გაჭრა
            $lastDot = mb_strrpos($piece, '. ');
            if ($lastDot !== false && $lastDot > $size * 0.5) {
                $piece = mb_substr($piece, 0, $lastDot + 1);
            }

            $pieces[] = trim($piece);
            $start += max(mb_strlen($piece) - $overlap, 1);
        }

        return array_filter($pieces);
    }
}
