<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => __('Please upload a file.'),
            'file.mimes' => __('File must be xlsx, xls, or csv format.'),
            'file.max' => __('File size must not exceed 5MB.'),
        ];
    }
}
