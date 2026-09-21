<?php

declare(strict_types=1);

namespace App\Http\Requests\Mock;

use Illuminate\Foundation\Http\FormRequest;

class CreateMockBogOrderRequest extends FormRequest
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
            'callback_url' => ['required', 'url'],
            'purchase_units' => ['required', 'array'],
            'purchase_units.total_amount' => ['required', 'numeric', 'min:0.01'],
            'purchase_units.currency' => ['required', 'string', 'size:3'],
            'purchase_units.basket' => ['required', 'array', 'min:1'],
            'purchase_units.basket.*.product_id' => ['required', 'string'],
            'purchase_units.basket.*.name' => ['required', 'string'],
            'purchase_units.basket.*.quantity' => ['required', 'integer', 'min:1'],
            'purchase_units.basket.*.unit_price' => ['required', 'numeric', 'min:0.01'],
            'redirect_urls' => ['required', 'array'],
            'redirect_urls.success' => ['required', 'url'],
            'redirect_urls.fail' => ['required', 'url'],
            'external_order_id' => ['required', 'string', 'max:255'],
            'save_card' => ['nullable', 'boolean'],
            'user_id' => ['nullable', 'integer'],
        ];
    }
}
