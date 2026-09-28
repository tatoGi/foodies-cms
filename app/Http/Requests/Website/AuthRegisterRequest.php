<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class AuthRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'phone' => ['required', new \App\Rules\GeorgianPhoneRule],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'locale' => ['nullable', 'in:ka,en'],
        ];
    }
}
