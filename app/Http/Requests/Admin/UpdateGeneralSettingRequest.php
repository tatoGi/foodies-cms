<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGeneralSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'header_logo' => trim((string) $this->input('header_logo', '')),
            'footer_logo' => trim((string) $this->input('footer_logo', '')),
            'footer_contact' => collect((array) $this->input('footer_contact', []))->map(static fn ($v): string => trim((string) $v))->all(),
            'social_links' => collect((array) $this->input('social_links', []))->map(static fn ($v): string => trim((string) $v))->all(),
            'contact_phone' => collect((array) $this->input('contact_phone', []))->map(static fn ($v): string => trim((string) $v))->all(),
            'contact_email' => collect((array) $this->input('contact_email', []))->map(static fn ($v): string => trim((string) $v))->all(),
            'contact_address' => collect((array) $this->input('contact_address', []))->map(static fn ($v): string => trim((string) $v))->all(),
        ]);
    }

    public function rules(): array
    {
        return [
            'header_logo' => ['nullable', 'string', 'max:500'],
            'footer_logo' => ['nullable', 'string', 'max:500'],
            'footer_contact' => ['nullable', 'array'],
            'footer_contact.*' => ['nullable', 'string', 'max:2000'],
            'social_links' => ['nullable', 'array'],
            'social_links.*' => ['nullable', 'string', 'max:500'],
            'contact_phone' => ['nullable', 'array'],
            'contact_phone.*' => ['nullable', 'string', 'max:100'],
            'contact_email' => ['nullable', 'array'],
            'contact_email.*' => ['nullable', 'string', 'max:255'],
            'contact_address' => ['nullable', 'array'],
            'contact_address.*' => ['nullable', 'string', 'max:500'],
        ];
    }
}
