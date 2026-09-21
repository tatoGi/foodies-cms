<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class PosHeartbeatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authenticated by AuthenticatePosDevice middleware
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'app_version' => ['required', 'string', 'max:50'],
            'kitchen_enabled' => ['sometimes', 'boolean'],
            'open' => ['sometimes', 'boolean'],
            'queue_depth' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
