<?php

namespace App\Services\AiChat;

use Illuminate\Support\Facades\DB;

class ChatService
{
    public function __construct(
        private RetrievalService $retrieval,
        private ModelRouter $router,
    ) {}

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{answer: string, provider: string, reason: string, sources: array}
     */
    public function ask(
        string $question,
        array $history = [],
        string $sessionId = '',
        bool $forceEscalate = false,
        bool $voice = false,
    ): array {
        $started = hrtime(true);

        // 1. RAG — რელევანტური კონტენტის მოძებნა
        $chunks = $this->retrieval->search($question);
        $context = $this->retrieval->buildContext($chunks);

        // 2. პრომპტის აწყობა
        $systemPrompt = config('aichat.system_prompt')
            ."\n\n=== საიტის კონტექსტი ===\n"
            .$context;

        // history შემოიფარგლება ბოლო 8 სვლით, რომ ტოკენები არ გაიბეროს
        $messages = array_merge(
            array_slice($history, -8),
            [['role' => 'user', 'content' => $question]]
        );

        // 3. ჰიბრიდული routing
        $result = $this->router->route(
            systemPrompt: $systemPrompt,
            messages: $messages,
            userQuestion: $question,
            historyTurns: count($history),
            forceEscalate: $forceEscalate,
        );

        // 4. ლოგი — სტატისტიკისთვის: რა წილი მიდის უფასოზე vs Claude-ზე
        DB::table('ai_chat_logs')->insert([
            'session_id' => $sessionId,
            'question' => mb_substr($question, 0, 5000),
            'answer' => mb_substr($result['answer'], 0, 10000),
            'provider' => $result['provider'],
            'route_reason' => $result['reason'],
            'latency_ms' => (int) ((hrtime(true) - $started) / 1e6),
            'voice' => $voice,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'answer' => $result['answer'],
            'provider' => $result['provider'],
            // 'default' = უფასო ჯაჭვმა უპასუხა → ვიჯეტი „უკეთესი პასუხის" ღილაკს აჩვენებს
            'reason' => $result['reason'],
            'sources' => $chunks->map(fn (object $c) => [
                'title' => $c->title,
                'url' => $c->meta['url'] ?? null,
            ])->unique('title')->values()->all(),
        ];
    }
}
