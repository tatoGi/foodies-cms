<?php

namespace App\Services\AiChat;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class EmbeddingService
{
    /**
     * ერთი ტექსტის embedding (query-სთვის).
     *
     * @return float[]
     */
    public function embed(string $text): array
    {
        return $this->embedBatch([$text])[0];
    }

    /**
     * რამდენიმე ტექსტის embedding ერთ request-ში (ინდექსაციისთვის).
     *
     * @param  string[]  $texts
     * @return float[][]
     */
    public function embedBatch(array $texts): array
    {
        $apiKey = config('aichat.providers.gemini.api_key');
        $model = config('aichat.embeddings.model');
        $dimensions = (int) config('aichat.embeddings.dimensions', 768);

        if (! $apiKey) {
            throw new RuntimeException('GEMINI_API_KEY არ არის მითითებული .env-ში');
        }

        // gemini-embedding-001 default-ად 3072 dim-ს აბრუნებს — outputDimensionality
        // აფიქსირებს config-ის ზომაზე, რომ query და ინდექსი ერთ სივრცეში იყოს.
        $requests = array_map(fn (string $t) => [
            'model' => "models/{$model}",
            'content' => ['parts' => [['text' => mb_substr($t, 0, 8000)]]],
            'outputDimensionality' => $dimensions,
        ], $texts);

        $response = Http::timeout(30)
            ->retry(2, 1500)
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:batchEmbedContents?key={$apiKey}",
                ['requests' => $requests]
            );

        if ($response->failed()) {
            throw new RuntimeException('Embedding API error: '.$response->status().' '.$response->body());
        }

        return array_map(
            fn (array $e) => $e['values'],
            $response->json('embeddings', [])
        );
    }
}
