<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GenerateBlockContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scope' => ['required', 'string', 'in:page,post,product'],
            'block_type' => ['required', 'string', 'max:100'],
            'locale' => ['required', 'string', 'max:8'],
            'context' => ['nullable', 'array'],
            'context.title' => ['nullable', 'string', 'max:500'],
            'context.description' => ['nullable', 'string', 'max:2000'],
            'context.instructions' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
