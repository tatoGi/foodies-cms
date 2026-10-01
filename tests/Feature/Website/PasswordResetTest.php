<?php

declare(strict_types=1);

namespace Tests\Feature\Website;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_answers_the_same_for_unknown_emails_and_sends_nothing(): void
    {
        Mail::fake();

        $this->postJson('/api/web/auth/password/forgot', ['email' => 'nobody@example.com'])->assertStatus(202);

        Mail::assertNothingQueued();
    }

    public function test_the_reset_code_sets_a_new_password_and_signs_out_everywhere(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'nino@example.com', 'password' => 'old-secret', 'email_verified_at' => now()]);
        $user->createToken('web', ['*'], now()->addDays(30));

        $this->postJson('/api/web/auth/password/forgot', ['email' => 'nino@example.com', 'locale' => 'en'])->assertStatus(202);
        $code = '';
        Mail::assertQueued(VerificationCodeMail::class, function (VerificationCodeMail $mail) use (&$code): bool {
            $code = $mail->code;

            return $mail->locale === 'en';
        });

        $this->postJson('/api/web/auth/password/reset', ['email' => 'nino@example.com', 'code' => $code, 'password' => 'new-secret-1'])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);

        $this->assertTrue(Hash::check('new-secret-1', (string) $user->fresh()->password));
        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_a_verify_code_cannot_reset_a_password(): void
    {
        Mail::fake();
        User::factory()->create(['email' => 'nino@example.com', 'email_verified_at' => now()]);
        $code = app(\App\Services\Website\VerificationCodeService::class)
            ->issue('nino@example.com', \App\Services\Website\VerificationCodeService::PURPOSE_VERIFY);

        $this->postJson('/api/web/auth/password/reset', ['email' => 'nino@example.com', 'code' => $code, 'password' => 'new-secret-1'])
            ->assertStatus(422)
            ->assertJsonPath('code', 'invalid_code');
    }
}
