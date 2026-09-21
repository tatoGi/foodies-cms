<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateFrontendUser
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = trim((string) $request->bearerToken());
        if ($token === '') {
            return new JsonResponse([
                'message' => 'Unauthorized.',
            ], 401);
        }

        $user = User::query()
            ->where('api_token', hash('sha256', $token))
            ->first();

        if (! $user instanceof User) {
            return new JsonResponse([
                'message' => 'Unauthorized.',
            ], 401);
        }

        Auth::guard('web')->setUser($user);
        $request->setUserResolver(static fn (): User => $user);

        return $next($request);
    }
}
