<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

use App\Rules\GeorgianPhoneRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', new GeorgianPhoneRule],
        ];
    }
}
