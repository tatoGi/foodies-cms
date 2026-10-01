<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Exceptions\WebApiException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/** Verifies a Google Identity Services ID token (signature via Google's JWKS, audience, issuer, verified e-mail). */
class GoogleIdTokenVerifier
{
    public const JWKS_URL = 'https://www.googleapis.com/oauth2/v3/certs';

    private const ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    /** @return array{sub: string, email: string, name: string} */
    public function verify(string $idToken): array
    {
        $clientId = (string) config('services.google.client_id');
        if ($clientId === '') {
            throw new WebApiException('google_disabled', 'Google sign-in is not configured.', 503);
        }

        $payload = $this->decode($idToken, retryWithFreshKeys: true);

        if (! in_array($payload->iss ?? null, self::ISSUERS, true)
            || ($payload->aud ?? null) !== $clientId
            || ($payload->email_verified ?? false) !== true
            || ! is_string($payload->email ?? null)
            || ! is_string($payload->sub ?? null)) {
            throw $this->invalid();
        }

        return [
            'sub' => $payload->sub,
            'email' => strtolower(trim($payload->email)),
            'name' => is_string($payload->name ?? null) ? $payload->name : '',
        ];
    }

    private function decode(string $idToken, bool $retryWithFreshKeys): object
    {
        try {
            $keys = Cache::remember('google-jwks', 3600, fn (): array => Http::timeout(5)->get(self::JWKS_URL)->throw()->json());

            return JWT::decode($idToken, JWK::parseKeySet($keys, 'RS256'));
        } catch (Throwable) {
            // Google rotates keys: one retry with a fresh key set before giving up.
            if ($retryWithFreshKeys) {
                Cache::forget('google-jwks');

                return $this->decode($idToken, retryWithFreshKeys: false);
            }

            throw $this->invalid();
        }
    }

    private function invalid(): WebApiException
    {
        return new WebApiException('invalid_google_token', 'Google sign-in failed.', 401);
    }
}
