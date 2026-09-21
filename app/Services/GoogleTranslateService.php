<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use RuntimeException;

class GoogleTranslateService
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {}

    /**
     * Translate a batch of strings to the target locale.
     * Uses Cloud Translation Basic (v2) API — requires only an API key.
     * Returns translated strings in the same order as input.
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

        $apiKey = (string) config('services.google_translate.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('Google Cloud Translation API key is not configured (GOOGLE_TRANSLATE_API_KEY).');
        }

        $target = $this->normalizeLocale($targetLocale);
        $source = $sourceLocale !== null && $sourceLocale !== '' ? $this->normalizeLocale($sourceLocale) : null;
        $timeout = (int) config('services.google_translate.timeout', 30);

        // Same language — return originals unchanged
        if ($source !== null && $source === $target) {
            return $texts;
        }

        $payload = [
            'q' => $texts,
            'target' => $target,
            'format' => 'html',
        ];

        if ($source !== null) {
            $payload['source'] = $source;
        }

        $response = $this->http
            ->timeout($timeout)
            ->acceptJson()
            ->post('https://translation.googleapis.com/language/translate/v2?key='.$apiKey, $payload);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException($this->resolveErrorMessage($exception));
        }

        $translations = (array) data_get($response->json(), 'data.translations', []);

        if (count($translations) !== count($texts)) {
            throw new RuntimeException('Google Translate returned unexpected number of translations.');
        }

        return array_map(
            static fn (array $item): string => (string) ($item['translatedText'] ?? ''),
            $translations
        );
    }

    /**
     * Translate a single string to the target locale.
     */
    public function translate(string $text, string $targetLocale, ?string $sourceLocale = null): string
    {
        if (trim($text) === '') {
            return $text;
        }

        $result = $this->translateBatch([$text], $targetLocale, $sourceLocale);

        return $result[0] ?? $text;
    }

    /**
     * Normalize locale codes like "en-US" → "en", "ka-GE" → "ka".
     */
    private function normalizeLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));

        // BCP-47 uses hyphens; Google Translate accepts short codes like "en", "ka"
        return explode('-', $locale)[0];
    }

    private function resolveErrorMessage(RequestException $exception): string
    {
        $response = $exception->response;
        $status = $response?->status();
        $apiMessage = trim((string) data_get($response?->json() ?? [], 'error.message', ''));

        if ($status === 400 && $apiMessage !== '') {
            return 'Google Translate API request error: '.$apiMessage;
        }

        if ($status === 401 || $status === 403) {
            return 'Google Translate API access denied ('.$status.'). შეამოწმე GOOGLE_TRANSLATE_API_KEY და GOOGLE_TRANSLATE_PROJECT_ID სწორობა და Cloud Translation API ჩართულია თუ არა პროექტზე.';
        }

        if ($status === 429) {
            return 'Google Translate API quota ამოიწურა (429). შეამოწმე Google Cloud Console-ში usage limits.';
        }

        if ($apiMessage !== '') {
            return 'Google Translate API error: '.$apiMessage;
        }

        return 'Google Translate API request failed (HTTP '.$status.').';
    }
}
