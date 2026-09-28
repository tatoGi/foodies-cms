<?php

declare(strict_types=1);

namespace App\Support;

final class GeorgianPhone
{
    /** "577 42 29 42", "+995 577-42-29-42", "995577422942" → "+995577422942"; anything else → null. */
    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';
        if (strlen($digits) === 12 && str_starts_with($digits, '9955')) {
            $digits = substr($digits, 3);
        }

        return preg_match('/^5\d{8}$/', $digits) === 1 ? '+995'.$digits : null;
    }
}
