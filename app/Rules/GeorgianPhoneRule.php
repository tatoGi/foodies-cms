<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\GeorgianPhone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GeorgianPhoneRule implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || GeorgianPhone::normalize($value) === null) {
            $fail('The :attribute must be a Georgian mobile number (5XX XXX XXX).');
        }
    }
}
