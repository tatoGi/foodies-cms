<?php

declare(strict_types=1);

namespace App\Http\Requests\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class StartBogCheckoutRequest extends FormRequest
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
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'delivery_address' => ['required', 'string', 'max:2000'],
            'save_card' => ['required', 'boolean'],
        ];
    }
}
