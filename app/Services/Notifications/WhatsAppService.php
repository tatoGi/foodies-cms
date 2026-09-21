<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class WhatsAppService
{
    /**
     * Send an approved Cloud-API template message.
     *
     * @param  list<string>  $bodyParameters  Ordered values that replace {{1}}, {{2}}, ...
     * @param  string|null  $recipient  E.164 digits without '+' (e.g. 995557422942). Defaults to services.whatsapp.recipient.
     * @param  string|null  $language  BCP-47 code matching the approved template (e.g. 'ka', 'en'). Defaults to services.whatsapp.default_language.
     * @return array{success:bool, message_id?:string, error?:string, status?:int}
     */
    public function sendTemplate(
        string $templateName,
        array $bodyParameters = [],
        ?string $recipient = null,
        ?string $language = null,
    ): array {
        if (! $this->isEnabled()) {
            Log::info('WhatsApp notification skipped: integration disabled.', [
                'template' => $templateName,
            ]);

            return ['success' => false, 'error' => 'whatsapp_disabled'];
        }

        $to = $this->normalizeRecipient($recipient ?? (string) config('services.whatsapp.recipient', ''));
        if ($to === '') {
            Log::warning('WhatsApp recipient is not configured.', ['template' => $templateName]);

            return ['success' => false, 'error' => 'recipient_missing'];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $language ?? (string) config('services.whatsapp.default_language', 'ka'),
                ],
            ],
        ];

        $components = $this->buildBodyComponents($bodyParameters);
        if ($components !== []) {
            $payload['template']['components'] = $components;
        }

        try {
            $response = Http::withToken((string) config('services.whatsapp.access_token'))
                ->timeout((int) config('services.whatsapp.timeout', 15))
                ->acceptJson()
                ->asJson()
                ->post($this->endpoint(), $payload);
        } catch (ConnectionException $e) {
            Log::error('WhatsApp request failed (connection).', [
                'template' => $templateName,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => 'connection_error'];
        }

        if ($response->failed()) {
            Log::error('WhatsApp API returned an error.', [
                'template' => $templateName,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);

            return [
                'success' => false,
                'error' => (string) data_get($response->json(), 'error.message', 'api_error'),
                'status' => $response->status(),
            ];
        }

        return [
            'success' => true,
            'message_id' => (string) data_get($response->json(), 'messages.0.id', ''),
        ];
    }

    public function isEnabled(): bool
    {
        if (! (bool) config('services.whatsapp.enabled', false)) {
            return false;
        }

        return (string) config('services.whatsapp.phone_number_id', '') !== ''
            && (string) config('services.whatsapp.access_token', '') !== '';
    }

    public function templateName(string $key): string
    {
        $name = (string) config("services.whatsapp.templates.{$key}", '');
        if ($name === '') {
            throw new RuntimeException("WhatsApp template '{$key}' is not configured.");
        }

        return $name;
    }

    /**
     * @param  list<string>  $parameters
     * @return list<array{type:string, parameters:list<array{type:string, text:string}>}>
     */
    private function buildBodyComponents(array $parameters): array
    {
        if ($parameters === []) {
            return [];
        }

        $mapped = array_map(
            static fn (string $value): array => ['type' => 'text', 'text' => $value === '' ? '—' : $value],
            array_values($parameters),
        );

        return [[
            'type' => 'body',
            'parameters' => $mapped,
        ]];
    }

    private function endpoint(): string
    {
        $version = (string) config('services.whatsapp.api_version', 'v21.0');
        $phoneId = (string) config('services.whatsapp.phone_number_id', '');

        return "https://graph.facebook.com/{$version}/{$phoneId}/messages";
    }

    private function normalizeRecipient(string $raw): string
    {
        return preg_replace('/\D+/', '', $raw) ?? '';
    }
}
