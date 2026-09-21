<?php

declare(strict_types=1);

namespace App\Http\Requests\Mock;

use Illuminate\Foundation\Http\FormRequest;

class CompleteMockGatewayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'result' => ['required', 'string', 'in:success,fail'],
        ];
    }
}
