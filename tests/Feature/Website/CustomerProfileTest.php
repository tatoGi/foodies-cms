<?php

declare(strict_types=1);

namespace Tests\Feature\Website;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    private function tokenFor(User $user): string
    {
        return $user->createToken('web', ['*'], now()->addDays(30))->plainTextToken;
    }

    public function test_profile_updates_name_and_normalized_phone_but_not_email(): void
    {
        $user = User::factory()->create(['email' => 'nino@example.com', 'email_verified_at' => now()]);

        $this->withToken($this->tokenFor($user))
            ->putJson('/api/web/me', ['name' => 'ნინო ბ.', 'phone' => '599 11 22 33', 'email' => 'evil@example.com'])
            ->assertOk()
            ->assertJsonPath('user.name', 'ნინო ბ.')
            ->assertJsonPath('user.phone', '+995599112233')
            ->assertJsonPath('user.email', 'nino@example.com');
    }

    public function test_profile_rejects_a_non_georgian_phone(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->withToken($this->tokenFor($user))
            ->putJson('/api/web/me', ['name' => 'Nino', 'phone' => '12345'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_password_change_needs_the_current_password_and_signs_out_other_devices(): void
    {
        $user = User::factory()->create(['password' => 'old-secret', 'email_verified_at' => now()]);
        $current = $this->tokenFor($user);
        $this->tokenFor($user);

        $this->withToken($current)
            ->putJson('/api/web/me/password', ['current_password' => 'wrong', 'password' => 'new-secret-1'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'invalid_password');

        $this->withToken($current)
            ->putJson('/api/web/me/password', ['current_password' => 'old-secret', 'password' => 'new-secret-1'])
            ->assertOk();

        $this->assertTrue(Hash::check('new-secret-1', (string) $user->fresh()->password));
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_a_google_only_user_can_set_a_password_without_a_current_one(): void
    {
        $user = User::factory()->create(['password' => null, 'google_id' => 'g-1', 'email_verified_at' => now()]);

        $this->withToken($this->tokenFor($user))
            ->putJson('/api/web/me/password', ['password' => 'first-secret'])
            ->assertOk()
            ->assertJsonPath('user.has_password', true);
    }

    public function test_account_deletion_requires_the_password_and_removes_everything(): void
    {
        $user = User::factory()->create(['password' => 'secret-pass', 'email_verified_at' => now()]);
        $token = $this->tokenFor($user);

        $this->withToken($token)->deleteJson('/api/web/me', ['password' => 'nope'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'invalid_password');

        $this->withToken($token)->deleteJson('/api/web/me', ['password' => 'secret-pass'])->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
