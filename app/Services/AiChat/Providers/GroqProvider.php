<?php

namespace App\Services\AiChat\Providers;

use Illuminate\Support\Facades\Http;

class GroqProvider implements LlmProvider
{
    public function name(): string
    {
        return 'groq';
    }

    public function chat(string $systemPrompt, array $messages): string
    {
        $cfg = config('aichat.providers.groq');

        $response = Http::timeout(45)
            ->withToken($cfg['api_key'])
            ->post($cfg['url'], [
                'model' => $cfg['model'],
                'temperature' => 0.3,
                'max_tokens' => 1024,
                'messages' => array_merge(
                    [['role' => 'system', 'content' => $systemPrompt]],
                    $messages
                ),
            ]);

        if ($response->status() === 429 || $response->serverError()) {
            throw new ProviderUnavailableException('Groq unavailable: '.$response->status());
        }

        if ($response->failed()) {
            throw new ProviderUnavailableException('Groq error: '.$response->body());
        }

        return trim($response->json('choices.0.message.content', ''));
    }
}
