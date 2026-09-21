<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

class AiContentService
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {}

    /**
     * Translate a batch of strings to the target locale.
     * Uses AI provider for translation.
     *
     * @param  string[]  $texts
     * @return string[]
     */
    public function translateBatch(array $texts, string $targetLocale, ?string $sourceLocale = null): array
    {
        $texts = array_values($texts);

        if ($texts === []) {
            return [];
        }

        $provider = $this->getTranslationProvider();
        $apiKey = $this->getTranslationApiKey($provider);
        $model = $this->getTranslationModel($provider);
        $baseUrl = rtrim($this->getTranslationBaseUrl($provider), '/');
        $timeout = (int) $this->getTranslationTimeout($provider);

        if ($apiKey === '') {
            throw new RuntimeException('No AI translation API key is configured for provider: '.$provider);
        }

        // Normalize locales (remove country code)
        $target = $this->normalizeLocale($targetLocale);
        $source = $sourceLocale !== null && $sourceLocale !== '' ? $this->normalizeLocale($sourceLocale) : null;

        // Same language — return originals unchanged
        if ($source !== null && $source === $target) {
            return $texts;
        }

        $prompt = $this->buildTranslationPrompt($texts, $target, $source);

        $response = $this->http
            ->timeout($timeout)
            ->acceptJson()
            ->withToken($apiKey)
            ->post($baseUrl.'/chat/completions', [
                'model' => $model,
                'temperature' => 0.2,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are a professional translator. Translate the following JSON array of strings from '.$source.' to '.$target.', '.
                            'Return valid JSON only in the same format as the input array. Do not add any explanations or markdown.',
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode($texts),
                    ],
                ],
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException($this->resolveApiErrorMessage($exception, $provider));
        }

        $text = (string) data_get($response->json(), 'choices.0.message.content', '');
        if ($text === '') {
            throw new RuntimeException($provider.' returned an empty response.');
        }

        $decoded = json_decode($this->cleanupJson($text), true);
        if (! is_array($decoded)) {
            throw new RuntimeException($provider.' returned invalid JSON for translation.');
        }

        // Ensure we have the same number of translations
        if (count($decoded) !== count($texts)) {
            throw new RuntimeException($provider.' returned unexpected number of translations.');
        }

        return array_map('trim', $decoded);
    }

    /**
     * Generate SEO JSON from payload.
     *
     * @return array<string, mixed>
     */
    public function generateSeoJson(string $prompt): array
    {
        $provider = $this->getSeoProvider();
        $apiKey = $this->getSeoApiKey($provider);
        $model = $this->getSeoModel($provider);
        $baseUrl = rtrim($this->getSeoBaseUrl($provider), '/');
        $timeout = (int) $this->getSeoTimeout($provider);

        if ($apiKey === '') {
            throw new RuntimeException('No AI SEO API key is configured for provider: '.$provider);
        }

        $response = $this->http
            ->timeout($timeout)
            ->acceptJson()
            ->withToken($apiKey)
            ->post($baseUrl.'/chat/completions', [
                'model' => $model,
                'temperature' => 0.2,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an SEO expert. Generate SEO metadata in JSON format based on the provided content. '.
                            'Return valid JSON only in this shape: {"meta_title":"","meta_description":"","keywords":["",""],"focus_keyword":""}. '.
                            'Do not use markdown code fences.',
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
            throw new RuntimeException($this->resolveApiErrorMessage($exception, $provider));
        }

        $text = (string) data_get($response->json(), 'choices.0.message.content', '');
        if ($text === '') {
            throw new RuntimeException($provider.' returned an empty response.');
        }

        $decoded = json_decode($this->cleanupJson($text), true);
        if (! is_array($decoded)) {
            throw new RuntimeException($provider.' returned invalid JSON for SEO.');
        }

        // Ensure required keys exist
        $decoded['meta_title'] = trim((string) ($decoded['meta_title'] ?? ''));
        $decoded['meta_description'] = trim((string) ($decoded['meta_description'] ?? ''));
        $decoded['focus_keyword'] = trim((string) ($decoded['focus_keyword'] ?? ''));
        $decoded['keywords'] = is_array($decoded['keywords']) ? $decoded['keywords'] : [];

        return $decoded;
    }

    /* Provider resolution methods */

    private function getTranslationProvider(): string
    {
        return strtolower((string) config('services.translation.provider', 'gemini'));
    }

    private function getTranslationApiKey(string $provider): string
    {
        return match ($provider) {
            'gemini' => (string) config('services.gemini.api_key'),
            'openai' => (string) config('services.openai.api_key'),
            'nim' => (string) config('services.nim.api_key'),
            'openrouter' => (string) config('services.openrouter.api_key'),
            default => '',
        };
    }

    private function getTranslationModel(string $provider): string
    {
        return match ($provider) {
            'gemini' => (string) config('services.gemini.model', 'gemini-2.5-flash'),
            'openai' => (string) config('services.openai.model', 'gpt-4o-mini'),
            'nim' => (string) config('services.nim.model', 'nim-rag-1b'),
            'openrouter' => (string) config('services.openrouter.model', 'openrouter/router'),
            default => '',
        };
    }

    private function getTranslationBaseUrl(string $provider): string
    {
        return match ($provider) {
            'gemini' => (string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'),
            'openai' => (string) config('services.openai.base_url', 'https://api.openai.com/v1'),
            'nim' => (string) config('services.nim.base_url', 'https://integrate.api.nvidia.com/v1'),
            'openrouter' => (string) config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'),
            default => '',
        };
    }

    private function getTranslationTimeout(string $provider): int
    {
        return match ($provider) {
            'gemini' => (int) config('services.gemini.timeout', 30),
            'openai' => (int) config('services.openai.timeout', 30),
            'nim' => (int) config('services.nim.timeout', 30),
            'openrouter' => (int) config('services.openrouter.timeout', 30),
            default => 30,
        };
    }

    private function getSeoProvider(): string
    {
        return strtolower((string) config('services.seo.provider', 'gemini'));
    }

    private function getSeoApiKey(string $provider): string
    {
        return match ($provider) {
            'gemini' => (string) config('services.gemini.api_key'),
            'openai' => (string) config('services.openai.api_key'),
            'nim' => (string) config('services.nim.api_key'),
            'openrouter' => (string) config('services.openrouter.api_key'),
            default => '',
        };
    }

    private function getSeoModel(string $provider): string
    {
        return match ($provider) {
            'gemini' => (string) config('services.gemini.model', 'gemini-2.5-flash'),
            'openai' => (string) config('services.openai.model', 'gpt-4o-mini'),
            'nim' => (string) config('services.nim.model', 'nim-rag-1b'),
            'openrouter' => (string) config('services.openrouter.model', 'openrouter/router'),
            default => '',
        };
    }

    private function getSeoBaseUrl(string $provider): string
    {
        return match ($provider) {
            'gemini' => (string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'),
            'openai' => (string) config('services.openai.base_url', 'https://api.openai.com/v1'),
            'nim' => (string) config('services.nim.base_url', 'https://integrate.api.nvidia.com/v1'),
            'openrouter' => (string) config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'),
            default => '',
        };
    }

    private function getSeoTimeout(string $provider): int
    {
        return match ($provider) {
            'gemini' => (int) config('services.gemini.timeout', 30),
            'openai' => (int) config('services.openai.timeout', 30),
            'nim' => (int) config('services.nim.timeout', 30),
            'openrouter' => (int) config('services.openrouter.timeout', 30),
            default => 30,
        };
    }

    /* Helper methods */

    private function buildTranslationPrompt(array $texts, string $target, ?string $source): string
    {
        $sourcePart = $source ?: 'auto-detected source language';

        return 'Translate the following JSON array of strings from '.$sourcePart.' to '.$target.': '.json_encode($texts);
    }

    private function normalizeLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));

        return explode('-', $locale)[0];
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
                return 'OpenAI API access denied (401). Verify that OPENAI_API_KEY is correct.';
            }

            if ($status === 403) {
                return 'OpenAI API access denied (403). Check project permissions, billing, and model access.';
            }

            if ($status === 429) {
                return 'OpenAI API rate limit or quota exceeded. Check billing and usage limits.';
            }

            if ($apiMessage !== '') {
                return 'OpenAI API error: '.$apiMessage;
            }

            return 'OpenAI API request failed.';
        }

        if ($provider === 'nim') {
            if ($status === 401 || $status === 403) {
                return 'NVIDIA NIM API access denied ('.$status.'). Verify that NVIDIA_NIM_API_KEY is correct and has access to the model.';
            }

            if ($status === 429) {
                return 'NVIDIA NIM API rate limit exceeded. Check usage limits.';
            }

            if ($apiMessage !== '') {
                return 'NVIDIA NIM API error: '.$apiMessage;
            }

            return 'NVIDIA NIM API request failed.';
        }

        if ($provider === 'openrouter') {
            if ($status === 401 || $status === 403) {
                return 'OpenRouter API access denied ('.$status.'). Verify that OPENROUTER_API_KEY is correct.';
            }

            if ($status === 429) {
                return 'OpenRouter API rate limit exceeded. Check usage limits.';
            }

            if ($apiMessage !== '') {
                return 'OpenRouter API error: '.$apiMessage;
            }

            return 'OpenRouter API request failed.';
        }

        // Default to Gemini error handling
        if ($status === 403) {
            return 'Gemini API access denied (403). Verify that Google AI Studio/Generative Language API is enabled, key is correct for the project, and key restrictions do not block server requests.';
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
