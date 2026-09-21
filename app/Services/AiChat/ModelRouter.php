<?php

namespace App\Services\AiChat;

use App\Services\AiChat\Providers\ClaudeProvider;
use App\Services\AiChat\Providers\GeminiProvider;
use App\Services\AiChat\Providers\GroqProvider;
use App\Services\AiChat\Providers\LlmProvider;
use App\Services\AiChat\Providers\OpenRouterProvider;
use App\Services\AiChat\Providers\ProviderUnavailableException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ჰიბრიდული routing:
 *
 *   1. ჯერ ვწყვეტთ, კითხვა "რთულია" თუ არა (escalation-ის წესები config-ში).
 *      რთული → პირდაპირ ფასიან მოდელზე, რომ ლიმიტი არ დაიხარჯოს ცუდ პასუხზე,
 *      რომელიც მაინც ხელახლა დასმას მოითხოვს.
 *   2. მარტივი → უფასო ჯაჭვი (groq → gemini → openrouter). 429/5xx-ზე შემდეგ
 *      რგოლზე გადადის, ბოლოს ფასიანზე, რომ მომხმარებელი უპასუხოდ არ დარჩეს.
 *
 * ფასიანი რგოლი config('aichat.paid')-ით ირჩევა: 'openrouter_paid'
 * (Claude Haiku OpenRouter-ით) ან 'claude' (პირდაპირი Anthropic API).
 */
class ModelRouter
{
    /** @var array{answer: string, provider: string, reason: string} */
    public function route(
        string $systemPrompt,
        array $messages,
        string $userQuestion,
        int $historyTurns,
        bool $forceEscalate = false,
    ): array {
        $reason = $this->escalationReason($userQuestion, $historyTurns, $forceEscalate);

        if ($reason !== null) {
            $paid = $this->paid();

            return [
                'answer' => $paid->chat($systemPrompt, $messages),
                'provider' => $paid->name(),
                'reason' => $reason,
            ];
        }

        foreach ($this->freeChain() as $provider) {
            try {
                $answer = $provider->chat($systemPrompt, $messages);

                // ცარიელი ან აშკარად ჩავარდნილი პასუხი → შემდეგი რგოლი
                if (Str::length(trim($answer)) < 2) {
                    continue;
                }

                return [
                    'answer' => $answer,
                    'provider' => $provider->name(),
                    'reason' => 'default',
                ];
            } catch (ProviderUnavailableException $e) {
                Log::info("aichat: {$provider->name()} fallback — {$e->getMessage()}");

                continue;
            }
        }

        // უფასო ჯაჭვი ამოიწურა → ფასიანი მოდელი როგორც უკანასკნელი fallback
        $paid = $this->paid();

        return [
            'answer' => $paid->chat($systemPrompt, $messages),
            'provider' => $paid->name(),
            'reason' => 'fallback',
        ];
    }

    private function escalationReason(string $question, int $historyTurns, bool $force): ?string
    {
        if ($force) {
            return 'user';
        }

        $cfg = config('aichat.routing');

        if (mb_strlen($question) > $cfg['escalate_message_length']) {
            return 'length';
        }

        if ($historyTurns >= $cfg['escalate_history_turns']) {
            return 'history';
        }

        $lower = mb_strtolower($question);
        foreach ($cfg['escalate_keywords'] as $keyword) {
            if (str_contains($lower, mb_strtolower($keyword))) {
                return 'keyword';
            }
        }

        return null;
    }

    /** @return LlmProvider[] */
    private function freeChain(): array
    {
        return collect(config('aichat.free_chain'))
            ->filter(fn (string $name) => config("aichat.providers.{$name}.api_key"))
            ->map(fn (string $name) => $this->make($name))
            ->all();
    }

    private function paid(): LlmProvider
    {
        return $this->make((string) config('aichat.paid', 'openrouter_paid'));
    }

    /**
     * პროვაიდერის აწყობა config-ის სახელით. უცნობი სახელი OpenRouter-ის
     * ჩანაწერად ითვლება (openrouter, openrouter_paid, ...).
     */
    private function make(string $name): LlmProvider
    {
        return match ($name) {
            'groq' => app(GroqProvider::class),
            'gemini' => app(GeminiProvider::class),
            'claude' => app(ClaudeProvider::class),
            default => new OpenRouterProvider($name),
        };
    }
}
