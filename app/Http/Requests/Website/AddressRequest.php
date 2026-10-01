<?php

declare(strict_types=1);

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Coordinates must fall inside Georgia's bounding box. */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:40'],
            'address_line' => ['required', 'string', 'max:255'],
            'entrance' => ['nullable', 'string', 'max:20'],
            'floor' => ['nullable', 'string', 'max:20'],
            'apartment' => ['nullable', 'string', 'max:20'],
            'lat' => ['required', 'numeric', 'between:41.0,43.7'],
            'lng' => ['required', 'numeric', 'between:39.9,46.8'],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }
}
