<?php

namespace App\Services\AiChat\Providers;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class ClaudeProvider implements LlmProvider
{
    public function name(): string
    {
        return 'claude';
    }

    public function chat(string $systemPrompt, array $messages): string
    {
        $cfg = config('aichat.providers.claude');

        $response = Http::timeout(60)
            ->withHeaders([
                'x-api-key' => $cfg['api_key'],
                'anthropic-version' => '2023-06-01',
            ])
            ->post($cfg['url'], [
                'model' => $cfg['model'],
                'max_tokens' => 1024,
                'system' => $systemPrompt,
                'messages' => $messages,
            ]);

        if ($response->failed()) {
            // ფასიანი ბოლო რგოლია — fallback აღარ გვაქვს, ამიტომ ჩვეულებრივი exception
            throw new RuntimeException('Claude API error: '.$response->status().' '.$response->body());
        }

        return trim(
            collect($response->json('content', []))
                ->where('type', 'text')
                ->pluck('text')
                ->implode("\n")
        );
    }
}
