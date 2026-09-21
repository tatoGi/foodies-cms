<?php

namespace App\Services\AiChat\Providers;

use Illuminate\Support\Facades\Http;

class GeminiProvider implements LlmProvider
{
    public function name(): string
    {
        return 'gemini';
    }

    public function chat(string $systemPrompt, array $messages): string
    {
        $cfg = config('aichat.providers.gemini');

        $contents = array_map(fn (array $m) => [
            'role' => $m['role'] === 'assistant' ? 'model' : 'user',
            'parts' => [['text' => $m['content']]],
        ], $messages);

        $response = Http::timeout(45)
            ->post("{$cfg['url']}/{$cfg['model']}:generateContent?key={$cfg['api_key']}", [
                'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
                'contents' => $contents,
                'generationConfig' => [
                    'temperature' => 0.3,
                    'maxOutputTokens' => 1024,
                ],
            ]);

        if ($response->status() === 429 || $response->serverError()) {
            throw new ProviderUnavailableException('Gemini unavailable: '.$response->status());
        }

        if ($response->failed()) {
            throw new ProviderUnavailableException('Gemini error: '.$response->body());
        }

        return trim($response->json('candidates.0.content.parts.0.text', ''));
    }
}
