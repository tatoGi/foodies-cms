<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderStatusRequest extends FormRequest
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
            'status' => [
                'required',
                'string',
                Rule::in([
                    Order::STATUS_PAID,
                    Order::STATUS_PROCESSING,
                    Order::STATUS_COMPLETED,
                    Order::STATUS_CANCELLED,
                ]),
            ],
        ];
    }
}
