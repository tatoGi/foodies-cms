<?php

declare(strict_types=1);

namespace Tests\Feature\Website;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerRegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function register(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/web/auth/register', array_merge([
            'name' => 'ნინო',
            'email' => 'Nino@Example.com',
            'phone' => '577 42 29 42',
            'password' => 'secret-pass',
            'locale' => 'ka',
        ], $overrides));
    }

    private function sentCode(): string
    {
        $code = '';
        Mail::assertQueued(VerificationCodeMail::class, function (VerificationCodeMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        return $code;
    }

    public function test_registration_sends_a_code_and_does_not_log_in(): void
    {
        Mail::fake();

        $this->register()->assertStatus(202)->assertJsonMissingPath('token');

        $user = User::query()->where('email', 'nino@example.com')->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->assertSame('+995577422942', $user->phone);
        Mail::assertQueued(VerificationCodeMail::class, fn (VerificationCodeMail $mail): bool => $mail->hasTo('nino@example.com'));
    }

    public function test_the_code_verifies_the_email_and_returns_a_working_token(): void
    {
        Mail::fake();
        $this->register();

        $token = $this->postJson('/api/web/auth/verify-email', ['email' => 'nino@example.com', 'code' => $this->sentCode()])
            ->assertOk()
            ->assertJsonPath('user.email', 'nino@example.com')
            ->json('token');

        $this->assertNotNull(User::query()->where('email', 'nino@example.com')->value('email_verified_at'));
        $this->withToken($token)->getJson('/api/web/me')->assertOk();
    }

    public function test_a_wrong_code_is_rejected(): void
    {
        Mail::fake();
        $this->register();
        $wrong = $this->sentCode() === '000000' ? '111111' : '000000';

        $this->postJson('/api/web/auth/verify-email', ['email' => 'nino@example.com', 'code' => $wrong])
            ->assertStatus(422)
            ->assertJsonPath('code', 'invalid_code');
    }

    public function test_registering_a_verified_email_looks_the_same_but_sends_nothing(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'nino@example.com', 'email_verified_at' => now()]);

        $this->register()->assertStatus(202);

        Mail::assertNothingQueued();
    }

    public function test_login_of_an_unverified_account_resends_the_code(): void
    {
        Mail::fake();
        $this->register();

        $this->postJson('/api/web/auth/login', ['email' => 'nino@example.com', 'password' => 'secret-pass'])
            ->assertStatus(403)
            ->assertJsonPath('code', 'email_not_verified');

        Mail::assertQueued(VerificationCodeMail::class, 2);
    }

    public function test_login_returns_a_token_and_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'nino@example.com', 'password' => 'secret-pass', 'email_verified_at' => now()]);

        $this->postJson('/api/web/auth/login', ['email' => 'NINO@example.com', 'password' => 'secret-pass'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'phone', 'has_password', 'google_linked']]);

        $this->postJson('/api/web/auth/login', ['email' => 'nino@example.com', 'password' => 'wrong-pass'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'invalid_credentials');
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $phone = $user->createToken('web', ['*'], now()->addDays(30))->plainTextToken;
        $laptop = $user->createToken('web', ['*'], now()->addDays(30))->plainTextToken;

        $this->withToken($phone)->postJson('/api/web/auth/logout')->assertOk();

        $this->assertSame(1, $user->tokens()->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($laptop)->getJson('/api/web/me')->assertOk();
    }

    public function test_login_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/web/auth/login', ['email' => 'nino@example.com', 'password' => 'wrong-pass']);
        }

        $this->postJson('/api/web/auth/login', ['email' => 'nino@example.com', 'password' => 'wrong-pass'])
            ->assertStatus(429);
    }
}
