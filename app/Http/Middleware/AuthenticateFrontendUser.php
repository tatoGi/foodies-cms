<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/** Site customers: Sanctum personal access token sent as Bearer by the Next.js server (never by the browser). */
class AuthenticateFrontendUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = self::userFromToken($request);
        if (! $user instanceof User) {
            return new JsonResponse(['message' => 'Unauthenticated.', 'code' => 'unauthenticated'], 401);
        }

        Auth::guard('web')->setUser($user);
        $request->setUserResolver(static fn (): User => $user);

        return $next($request);
    }

    /** The token's user, or null when the token is missing, unknown or expired. */
    public static function userFromToken(Request $request): ?User
    {
        $plain = trim((string) $request->bearerToken());
        if ($plain === '') {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($plain);
        if (! $accessToken instanceof PersonalAccessToken
            || ($accessToken->expires_at !== null && $accessToken->expires_at->isPast())) {
            return null;
        }

        $user = $accessToken->tokenable;
        if (! $user instanceof User) {
            return null;
        }

        $accessToken->forceFill(['last_used_at' => now()])->save();

        return $user->withAccessToken($accessToken);
    }
}
