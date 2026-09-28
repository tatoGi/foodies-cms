<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class PasswordChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }
}
