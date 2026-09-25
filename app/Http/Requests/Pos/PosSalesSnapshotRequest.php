<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class PosSalesSnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $money = ['required', 'numeric', 'min:0', 'max:9999999999'];

        return [
            'days' => ['required', 'array', 'min:1', 'max:400'],
            'days.*.date' => ['required', 'date_format:Y-m-d', 'distinct'],
            'days.*.bills' => ['required', 'integer', 'min:0'],
            'days.*.gross' => $money,
            'days.*.discount' => $money,
            'days.*.net' => $money,
            'days.*.cash' => $money,
            'days.*.card' => $money,
            'days.*.other' => $money,
            'days.*.products' => ['present', 'array'],
            'days.*.products.*.external_id' => ['required', 'integer', 'min:1'],
            'days.*.products.*.title' => ['required', 'string', 'max:255'],
            'days.*.products.*.quantity' => ['required', 'integer', 'min:0'],
            'days.*.products.*.revenue' => $money,
        ];
    }
}
