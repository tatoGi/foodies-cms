<?php

declare(strict_types=1);

namespace Tests\Feature\Website;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendTokenAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_sanctum_token_authenticates_the_site_user(): void
    {
        $user = User::factory()->create(['email' => 'nino@example.com']);
        $token = $user->createToken('web', ['*'], now()->addDays(30))->plainTextToken;

        $this->withToken($token)->getJson('/api/web/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'nino@example.com');
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('web', ['*'], now()->subMinute())->plainTextToken;

        $this->withToken($token)->getJson('/api/web/me')
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');
    }

    public function test_requests_without_a_token_are_rejected(): void
    {
        $this->getJson('/api/web/me')->assertUnauthorized();
    }
}
