<?php

declare(strict_types=1);

namespace Tests\Feature\Website;

use App\Models\User;
use App\Services\Website\GoogleIdTokenVerifier;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private string $privateKey;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => 'test-client.apps.googleusercontent.com']);

        // On Windows/Laragon, openssl may need its config: set OPENSSL_CONF or pass ['config' => '<php>/extras/ssl/openssl.cnf'].
        $options = array_filter(['config' => getenv('OPENSSL_CONF') ?: null]);
        $key = openssl_pkey_new($options + ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem, null, $options);
        $this->privateKey = $pem;
        $rsa = openssl_pkey_get_details($key)['rsa'];
        $b64 = static fn (string $bin): string => rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');

        Http::fake([GoogleIdTokenVerifier::JWKS_URL => Http::response(['keys' => [[
            'kty' => 'RSA', 'alg' => 'RS256', 'use' => 'sig', 'kid' => 'test-kid',
            'n' => $b64($rsa['n']), 'e' => $b64($rsa['e']),
        ]]])]);
    }

    private function idToken(array $overrides = []): string
    {
        return JWT::encode(array_merge([
            'iss' => 'https://accounts.google.com',
            'aud' => 'test-client.apps.googleusercontent.com',
            'sub' => 'google-123',
            'email' => 'Nino@Gmail.com',
            'email_verified' => true,
            'name' => 'Nino B',
            'iat' => time(),
            'exp' => time() + 600,
        ], $overrides), $this->privateKey, 'RS256', 'test-kid');
    }

    public function test_a_new_google_user_gets_a_verified_passwordless_account(): void
    {
        $this->postJson('/api/web/auth/google', ['id_token' => $this->idToken()])
            ->assertOk()
            ->assertJsonPath('user.email', 'nino@gmail.com')
            ->assertJsonPath('user.has_password', false)
            ->assertJsonPath('user.google_linked', true);

        $user = User::query()->where('email', 'nino@gmail.com')->firstOrFail();
        $this->assertSame('google-123', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_an_unverified_password_account_is_linked_and_its_password_dropped(): void
    {
        // Someone registered this e-mail with a password but never confirmed it: the Google owner takes over safely.
        User::factory()->create(['email' => 'nino@gmail.com', 'password' => 'squatter', 'email_verified_at' => null]);

        $this->postJson('/api/web/auth/google', ['id_token' => $this->idToken()])->assertOk();

        $user = User::query()->where('email', 'nino@gmail.com')->firstOrFail();
        $this->assertNull($user->password);
        $this->assertSame('google-123', $user->google_id);
    }

    public function test_tokens_for_another_client_or_unverified_emails_are_rejected(): void
    {
        $this->postJson('/api/web/auth/google', ['id_token' => $this->idToken(['aud' => 'someone-else'])])
            ->assertStatus(401)->assertJsonPath('code', 'invalid_google_token');
        $this->postJson('/api/web/auth/google', ['id_token' => $this->idToken(['email_verified' => false])])
            ->assertStatus(401);
        $this->postJson('/api/web/auth/google', ['id_token' => 'not-a-jwt'])
            ->assertStatus(401);
    }
}
