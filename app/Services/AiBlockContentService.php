<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\BlockTypeRepositoryInterface;
use App\Services\AiChat\Providers\OpenRouterProvider;
use App\Services\AiChat\Providers\ProviderUnavailableException;
use RuntimeException;

/**
 * Generates fresh localized content for a single CMS block from its schema,
 * using the website context (name, page title/description) as grounding.
 *
 * Uses the OpenRouter provider (free model by default). Only text-like and
 * repeater fields are generated — media/select/number fields are left untouched.
 */
class AiBlockContentService
{
    /** Field types whose value is a plain generatable string. */
    private const TEXT_TYPES = ['text', 'textarea', 'rich_text', 'string'];

    public function __construct(
        private readonly BlockTypeRepositoryInterface $blockTypeRepository,
    ) {}

    /**
     * @param  array<string,mixed>  $context  optional grounding: title, description, instructions
     * @return array<string,mixed> block data keyed by field key
     */
    public function generate(string $scope, string $blockType, string $locale, array $context = []): array
    {
        $definition = $this->blockTypeRepository
            ->getEnabledForScope($scope)
            ->first(static fn ($bt): bool => (string) $bt->key === $blockType);

        if ($definition === null) {
            throw new RuntimeException("Unknown block type [{$blockType}] for scope [{$scope}].");
        }

        $fields = (array) data_get($definition->schema, 'fields', []);
        $generatable = $this->generatableFields($fields);

        if ($generatable === []) {
            return [];
        }

        $system = $this->systemPrompt($locale);
        $messages = [['role' => 'user', 'content' => $this->userPrompt($definition, $generatable, $locale, $context)]];

        return $this->coerce($generatable, $this->decodeJson($this->ask($system, $messages)));
    }

    /**
     * Ask OpenRouter, falling back from the free model to the paid one when the
     * free tier is rate-limited/unavailable (both use the same OPENROUTER_API_KEY).
     *
     * @param  array<int, array{role:string, content:string}>  $messages
     */
    private function ask(string $system, array $messages): string
    {
        $lastError = null;

        foreach ($this->providerChain() as $configKey) {
            try {
                return $this->provider($configKey)->chat($system, $messages);
            } catch (ProviderUnavailableException $e) {
                $lastError = $e;
            }
        }

        throw $lastError ?? new RuntimeException('No AI provider is available.');
    }

    /**
     * @return array<int, string>
     */
    protected function providerChain(): array
    {
        return ['openrouter', 'openrouter_paid'];
    }

    protected function provider(string $configKey): OpenRouterProvider
    {
        return new OpenRouterProvider($configKey);
    }

    /**
     * Keep only fields we can meaningfully generate text for. Repeater fields
     * keep their generatable sub-fields.
     *
     * @param  array<int,array<string,mixed>>  $fields
     * @return array<int,array<string,mixed>>
     */
    private function generatableFields(array $fields): array
    {
        $result = [];

        foreach ($fields as $field) {
            $key = trim((string) ($field['key'] ?? ''));
            $type = (string) ($field['type'] ?? 'text');

            if ($key === '') {
                continue;
            }

            if (in_array($type, self::TEXT_TYPES, true)) {
                $result[] = ['key' => $key, 'type' => $type, 'label' => (string) ($field['label'] ?? $key)];

                continue;
            }

            if ($type === 'repeater') {
                $subFields = $this->generatableFields((array) ($field['fields'] ?? []));
                if ($subFields !== []) {
                    $result[] = ['key' => $key, 'type' => 'repeater', 'label' => (string) ($field['label'] ?? $key), 'fields' => $subFields];
                }
            }
        }

        return $result;
    }

    private function systemPrompt(string $locale): string
    {
        $language = $this->languageName($locale);

        return 'You are a senior website copywriter for a Georgian furniture & lighting e-commerce brand. '
            ."Write concise, natural, conversion-oriented marketing copy in {$language}. "
            .'Return ONLY a valid minified JSON object — no markdown, no code fences, no commentary.';
    }

    /**
     * @param  array<int,array<string,mixed>>  $fields
     * @param  array<string,mixed>  $context
     */
    private function userPrompt(object $definition, array $fields, string $locale, array $context): string
    {
        $siteName = (string) config('app.name');
        $blockLabel = (string) ($definition->label ?? $definition->key);
        $blockDesc = trim((string) ($definition->description ?? ''));

        $title = trim((string) ($context['title'] ?? ''));
        $description = trim((string) ($context['description'] ?? ''));
        $instructions = trim((string) ($context['instructions'] ?? ''));

        $lines = [];
        $lines[] = "Website: {$siteName}";
        if ($title !== '') {
            $lines[] = "Page title: {$title}";
        }
        if ($description !== '') {
            $lines[] = "Page description: {$description}";
        }
        $lines[] = "Block: {$blockLabel}".($blockDesc !== '' ? " — {$blockDesc}" : '');
        if ($instructions !== '') {
            $lines[] = "Extra instructions: {$instructions}";
        }
        $lines[] = 'Language: '.$this->languageName($locale);
        $lines[] = '';
        $lines[] = 'Produce content for this exact JSON shape (fill every field with realistic copy):';
        $lines[] = $this->shapeDescription($fields);

        return implode("\n", $lines);
    }

    /**
     * Human-readable JSON shape hint for the prompt.
     *
     * @param  array<int,array<string,mixed>>  $fields
     */
    private function shapeDescription(array $fields): string
    {
        $shape = [];

        foreach ($fields as $field) {
            if (($field['type'] ?? '') === 'repeater') {
                $item = [];
                foreach ((array) $field['fields'] as $sub) {
                    $item[(string) $sub['key']] = $this->placeholderFor($sub);
                }
                $shape[(string) $field['key']] = [$item, $item, $item];

                continue;
            }

            $shape[(string) $field['key']] = $this->placeholderFor($field);
        }

        return (string) json_encode($shape, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * @param  array<string,mixed>  $field
     */
    private function placeholderFor(array $field): string
    {
        $type = (string) ($field['type'] ?? 'text');
        $label = (string) ($field['label'] ?? $field['key'] ?? '');

        return match ($type) {
            'rich_text' => "<short HTML paragraph for: {$label}>",
            'textarea' => "<2-3 sentence text for: {$label}>",
            default => "<short text for: {$label}>",
        };
    }

    /**
     * Keep only known field keys; cast to the expected shape.
     *
     * @param  array<int,array<string,mixed>>  $fields
     * @param  array<string,mixed>  $parsed
     * @return array<string,mixed>
     */
    private function coerce(array $fields, array $parsed): array
    {
        $result = [];

        foreach ($fields as $field) {
            $key = (string) $field['key'];

            if (($field['type'] ?? '') === 'repeater') {
                $rows = $parsed[$key] ?? null;
                if (! is_array($rows)) {
                    continue;
                }

                $items = [];
                foreach ($rows as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    $item = [];
                    foreach ((array) $field['fields'] as $sub) {
                        $subKey = (string) $sub['key'];
                        $item[$subKey] = trim((string) ($row[$subKey] ?? ''));
                    }
                    if (implode('', $item) !== '') {
                        $items[] = $item;
                    }
                }

                if ($items !== []) {
                    $result[$key] = $items;
                }

                continue;
            }

            $value = $parsed[$key] ?? null;
            if (is_scalar($value) && trim((string) $value) !== '') {
                $result[$key] = trim((string) $value);
            }
        }

        return $result;
    }

    /**
     * Tolerant JSON extraction — strips code fences and isolates the JSON object.
     *
     * @return array<string,mixed>
     */
    private function decodeJson(string $raw): array
    {
        $text = trim($raw);
        $text = preg_replace('/^```(?:json)?|```$/m', '', $text) ?? $text;

        $start = strpos($text, '{');
        $end = strrpos($text, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $text = substr($text, $start, $end - $start + 1);
        }

        $decoded = json_decode(trim($text), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('AI returned an unparseable response.');
        }

        return $decoded;
    }

    private function languageName(string $locale): string
    {
        return match (strtolower(substr(trim($locale), 0, 2))) {
            'ka' => 'Georgian (ქართული)',
            'en' => 'English',
            'ru' => 'Russian (русский)',
            default => $locale,
        };
    }
}
