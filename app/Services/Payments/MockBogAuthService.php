<?php

declare(strict_types=1);

namespace App\Services\Payments;

use Bog\Payment\Services\BogAuthService;

class MockBogAuthService extends BogAuthService
{
    /**
     * @return array{access_token:string,token_type:string,expires_in:int}
     */
    public function getAccessToken()
    {
        return [
            'access_token' => 'mock-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ];
    }
}
