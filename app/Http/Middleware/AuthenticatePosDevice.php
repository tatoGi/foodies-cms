<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Pos\PosDeviceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates a FoodEase POS instance: Bearer token identifies the device,
 * X-Signature = HMAC-SHA256(secret, "<X-Timestamp>.<raw body>") proves the body is untampered.
 */
class AuthenticatePosDevice
{
    private const MAX_CLOCK_SKEW_SECONDS = 300;

    public function __construct(private readonly PosDeviceService $devices) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->bearerToken();
        $timestamp = (string) $request->header('X-Timestamp');
        $signature = (string) $request->header('X-Signature');

        $device = $token !== '' ? $this->devices->findActiveByToken($token) : null;

        if ($device === null
            || ! ctype_digit($timestamp)
            || abs(time() - (int) $timestamp) > self::MAX_CLOCK_SKEW_SECONDS
            || ! hash_equals(hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $device->signing_secret), $signature)
        ) {
            return response()->json(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $request->attributes->set('pos_device', $device);

        return $next($request);
    }
}
