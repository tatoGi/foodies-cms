<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password:admin'],
            'new_password' => ['required', 'string', 'min:8', 'different:current_password', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.current_password' => __('Current password is incorrect.'),
            'new_password.confirmed' => __('Password confirmation does not match.'),
        ];
    }
}
