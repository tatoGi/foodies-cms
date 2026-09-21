<?php

namespace App\Http\Controllers;

use App\Services\AiChat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class AiChatController extends Controller
{
    public function chat(Request $request, ChatService $chat): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'history' => ['array', 'max:20'],
            'history.*.role' => ['required_with:history', 'in:user,assistant'],
            'history.*.content' => ['required_with:history', 'string', 'max:5000'],
            'session_id' => ['nullable', 'string', 'max:64'],
            'escalate' => ['boolean'],
            'voice' => ['boolean'],
        ]);

        try {
            $result = $chat->ask(
                question: $data['message'],
                history: $data['history'] ?? [],
                sessionId: $data['session_id'] ?? (string) Str::uuid(),
                forceEscalate: (bool) ($data['escalate'] ?? false),
                voice: (bool) ($data['voice'] ?? false),
            );

            return response()->json($result);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'answer' => 'სამწუხაროდ, ამ წუთას პასუხის გაცემა ვერ ხერხდება. სცადეთ ცოტა ხანში.',
                'provider' => null,
                'sources' => [],
            ], 503);
        }
    }

    /**
     * ხმოვანი ფაილის ტრანსკრიფცია (Groq Whisper, უფასო tier).
     * Frontend აგზავნის ჩაწერილ audio blob-ს, უკან ბრუნდება ტექსტი —
     * რომელსაც ვიჯეტი ჩვეულებრივ chat endpoint-ში უშვებს.
     */
    public function transcribe(Request $request): JsonResponse
    {
        $request->validate([
            'audio' => ['required', 'file', 'max:10240'], // 10MB
        ]);

        $cfg = config('aichat.voice');
        $file = $request->file('audio');

        try {
            $response = Http::timeout(60)
                ->withToken(config('aichat.providers.groq.api_key'))
                ->attach('file', file_get_contents($file->getRealPath()), 'audio.webm')
                ->post($cfg['stt_url'], [
                    'model' => $cfg['stt_model'],
                    'language' => $cfg['language'],
                ]);

            if ($response->failed()) {
                report(new \RuntimeException('Whisper error: '.$response->body()));

                return response()->json(['text' => null, 'error' => 'ტრანსკრიფცია ვერ მოხერხდა.'], 503);
            }

            return response()->json(['text' => trim($response->json('text', ''))]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['text' => null, 'error' => 'ტრანსკრიფცია ვერ მოხერხდა.'], 503);
        }
    }
}
