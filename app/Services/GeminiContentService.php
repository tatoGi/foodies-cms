<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

class GeminiContentService
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function generateJson(string $prompt): array
    {
        $openAiApiKey = (string) config('services.openai.api_key');

        if ($openAiApiKey !== '') {
            return $this->generateWithOpenAi($prompt, $openAiApiKey);
        }

        return $this->generateWithGemini($prompt);
    }

    /**
     * @return array<string, mixed>
     */
    public function generateSeoJson(string $prompt): array
    {
        $openAiApiKey = (string) config('services.openai.api_key');

        if ($openAiApiKey === '') {
            throw new RuntimeException('OpenAI API key is not configured for SEO generation.');
        }

        return $this->generateWithOpenAi($prompt, $openAiApiKey);
    }

    /**
     * @return array<string, mixed>
     */
    private function generateWithGemini(string $prompt): array
    {
        $apiKey = (string) config('services.gemini.api_key');
        $model = (string) config('services.gemini.model', 'gemini-2.5-flash');
        $baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $timeout = (int) config('services.gemini.timeout', 30);

        if ($apiKey === '') {
            throw new RuntimeException('No AI API key is configured.');
        }

        $response = $this->http
            ->timeout($timeout)
            ->acceptJson()
            ->post(sprintf('%s/models/%s:generateContent?key=%s', $baseUrl, $model, $apiKey), [
                'contents' => [[
                    'parts' => [[
                        'text' => $prompt,
                    ]],
                ]],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'responseMimeType' => 'application/json',
                ],
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException($this->resolveApiErrorMessage($exception, 'gemini'));
        }

        $text = (string) data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        if ($text === '') {
            throw new RuntimeException('Gemini returned an empty response.');
        }

        $decoded = json_decode($this->cleanupJson($text), true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini returned invalid JSON.');
        }

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function generateWithOpenAi(string $prompt, string $apiKey): array
    {
        $model = (string) config('services.openai.model', 'gpt-4o-mini');
        $baseUrl = rtrim((string) config('services.openai.base_url', 'https://api.openai.com/v1'), '/');
        $timeout = (int) config('services.openai.timeout', 30);

        $response = $this->http
            ->timeout($timeout)
            ->acceptJson()
            ->withToken($apiKey)
            ->post($baseUrl.'/chat/completions', [
                'model' => $model,
                'temperature' => 0.2,
                'messages' => [
                    [
                        'role' => 'developer',
                        'content' => 'Return valid JSON only. Do not use markdown code fences.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException($this->resolveApiErrorMessage($exception, 'openai'));
        }

        $text = (string) data_get($response->json(), 'choices.0.message.content', '');
        if ($text === '') {
            throw new RuntimeException('OpenAI returned an empty response.');
        }

        $decoded = json_decode($this->cleanupJson($text), true);
        if (! is_array($decoded)) {
            throw new RuntimeException('OpenAI returned invalid JSON.');
        }

        return $decoded;
    }

    private function cleanupJson(string $text): string
    {
        $cleaned = trim($text);

        if (str_starts_with($cleaned, '```')) {
            $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned) ?? $cleaned;
            $cleaned = preg_replace('/\s*```$/', '', $cleaned) ?? $cleaned;
        }

        return trim($cleaned);
    }

    private function resolveApiErrorMessage(RequestException $exception, string $provider): string
    {
        $response = $exception->response;
        $status = $response?->status();
        $apiMessage = trim((string) data_get($response?->json() ?? [], 'error.message', ''));

        if ($provider === 'openai') {
            if ($status === 401) {
                return 'OpenAI API access denied (401). გადაამოწმე რომ OPENAI_API_KEY სწორია.';
            }

            if ($status === 403) {
                return 'OpenAI API access denied (403). გადაამოწმე project permissions, billing და model access.';
            }

            if ($status === 429) {
                return 'OpenAI API rate limit ან quota ამოიწურა. გადაამოწმე billing და usage limits.';
            }

            if ($apiMessage !== '') {
                return 'OpenAI API error: '.$apiMessage;
            }

            return 'OpenAI API request failed.';
        }

        if ($status === 403) {
            return 'Gemini API access denied (403). გადაამოწმე რომ Google AI Studio/Generative Language API ჩართულია, key სწორ პროექტზეა შექმნილი, და key restrictions server request-ს არ ბლოკავს.';
        }

        if ($status === 400 && $apiMessage !== '') {
            return 'Gemini API request rejected: '.$apiMessage;
        }

        if ($apiMessage !== '') {
            return 'Gemini API error: '.$apiMessage;
        }

        return 'Gemini API request failed.';
    }
}
