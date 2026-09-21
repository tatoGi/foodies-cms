<?php

declare(strict_types=1);

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class ShowCheckoutResultRequest extends FormRequest
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
            'order_id' => ['nullable', 'string', 'max:255'],
            'external_order_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
