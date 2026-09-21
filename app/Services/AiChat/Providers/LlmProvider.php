<?php

namespace App\Services\AiChat\Providers;

interface LlmProvider
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     *
     * @throws ProviderUnavailableException 429/5xx-ზე — router გადადის შემდეგზე
     */
    public function chat(string $systemPrompt, array $messages): string;

    public function name(): string;
}
