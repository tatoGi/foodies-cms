<?php

namespace App\Services\AiChat\Providers;

use Illuminate\Support\Facades\Http;

/**
 * OpenRouter — OpenAI-თავსებადი gateway (openrouter.ai).
 *
 * ერთი და იგივე კლასი ემსახურება free chain-საც და ფასიან escalation-საც:
 * $configKey მიუთითებს config('aichat.providers.*')-ის შესაბამის ჩანაწერზე
 * (მაგ. 'openrouter' — უფასო მოდელი, 'openrouter_paid' — Claude Haiku).
 */
class OpenRouterProvider implements LlmProvider
{
    public function __construct(private string $configKey = 'openrouter') {}

    public function name(): string
    {
        return $this->configKey;
    }

    public function chat(string $systemPrompt, array $messages): string
    {
        $cfg = config("aichat.providers.{$this->configKey}");

        $response = Http::timeout(60)
            ->withToken($cfg['api_key'])
            ->withHeaders([
                // OpenRouter-ის რეკომენდებული ატრიბუციის ჰედერები
                'HTTP-Referer' => (string) config('app.url'),
                'X-Title' => (string) config('app.name'),
            ])
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
            throw new ProviderUnavailableException('OpenRouter unavailable: '.$response->status());
        }

        if ($response->failed()) {
            throw new ProviderUnavailableException('OpenRouter error: '.$response->body());
        }

        return trim($response->json('choices.0.message.content', ''));
    }
}
