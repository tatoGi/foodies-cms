<?php

declare(strict_types=1);

namespace App\Http\Requests\Mock;

use Illuminate\Foundation\Http\FormRequest;

class ShowMockPaymentDetailsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'orderId' => $this->route('orderId'),
        ]);
    }

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
            'orderId' => ['nullable', 'string', 'max:255'],
        ];
    }
}
