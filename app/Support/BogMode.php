<?php

declare(strict_types=1);

namespace App\Support;

class BogMode
{
    public static function hasCredentials(): bool
    {
        return filled((string) config('bog-payment.client_id'))
            && filled((string) config('bog-payment.client_secret'));
    }

    public static function isMockMode(): bool
    {
        return (bool) config('bog-payment.mock_mode', false)
            || ! self::hasCredentials();
    }
}
