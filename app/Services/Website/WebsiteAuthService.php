<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Exceptions\WebApiException;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Support\GeorgianPhone;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class WebsiteAuthService
{
    public function __construct(
        private readonly VerificationCodeService $codes,
        private readonly GoogleIdTokenVerifier $google,
    ) {}

    public static function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public static function mailLocale(?string $locale): string
    {
        return $locale === 'en' ? 'en' : 'ka';
    }

    /** Creates (or refreshes an unverified) account and e-mails a code. A verified e-mail gets the same answer and no mail. */
    public function register(array $data, string $locale): void
    {
        $email = self::normalizeEmail((string) $data['email']);
        $user = User::query()->where('email', $email)->first();
        if ($user instanceof User && $user->email_verified_at !== null) {
            return;
        }

        $attributes = [
            'name' => trim((string) $data['name']),
            'phone' => GeorgianPhone::normalize((string) $data['phone']),
            'password' => (string) $data['password'],
        ];
        $user instanceof User
            ? $user->update($attributes)
            : User::query()->create(['email' => $email] + $attributes);

        $this->sendCode($email, VerificationCodeService::PURPOSE_VERIFY, $locale);
    }

    /** @return array{token: string, user: array<string, mixed>} */
    public function verifyEmail(string $email, string $code): array
    {
        $email = self::normalizeEmail($email);
        $user = User::query()->where('email', $email)->first();
        if (! $user instanceof User || ! $this->codes->consume($email, VerificationCodeService::PURPOSE_VERIFY, $code)) {
            throw new WebApiException('invalid_code', 'The code is invalid or has expired.');
        }

        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $this->session($user);
    }

    public function resendVerification(string $email, string $locale): void
    {
        $email = self::normalizeEmail($email);
        $user = User::query()->where('email', $email)->first();
        if ($user instanceof User && $user->email_verified_at === null) {
            $this->sendCode($email, VerificationCodeService::PURPOSE_VERIFY, $locale);
        }
    }

    /** @return array{token: string, user: array<string, mixed>} */
    public function login(array $data, string $locale): array
    {
        $email = self::normalizeEmail((string) $data['email']);
        $user = User::query()->where('email', $email)->first();
        if (! $user instanceof User || $user->password === null || ! Hash::check((string) $data['password'], $user->password)) {
            throw new WebApiException('invalid_credentials', 'The e-mail or password is incorrect.');
        }

        if ($user->email_verified_at === null) {
            $this->sendCode($email, VerificationCodeService::PURPOSE_VERIFY, $locale);
            throw new WebApiException('email_not_verified', 'Confirm your e-mail first; a new code was sent.', 403);
        }

        return $this->session($user);
    }

    public function forgotPassword(string $email, string $locale): void
    {
        $email = self::normalizeEmail($email);
        $user = User::query()->where('email', $email)->first();
        if ($user instanceof User && $user->email_verified_at !== null) {
            $this->sendCode($email, VerificationCodeService::PURPOSE_RESET, $locale);
        }
    }

    /** @return array{token: string, user: array<string, mixed>} */
    public function resetPassword(string $email, string $code, string $password): array
    {
        $email = self::normalizeEmail($email);
        $user = User::query()->where('email', $email)->first();
        if (! $user instanceof User || ! $this->codes->consume($email, VerificationCodeService::PURPOSE_RESET, $code)) {
            throw new WebApiException('invalid_code', 'The code is invalid or has expired.');
        }

        $user->forceFill(['password' => $password])->save();
        $user->tokens()->delete();

        return $this->session($user);
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /** @return array{token: string, user: array<string, mixed>} */
    public function session(User $user): array
    {
        return [
            'token' => $user->createToken('web', ['*'], now()->addDays(30))->plainTextToken,
            'user' => $this->userPayload($user),
        ];
    }

    /** @return array{id: int, name: string, email: string, phone: string, has_password: bool, google_linked: bool} */
    public function userPayload(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'phone' => (string) ($user->phone ?? ''),
            'has_password' => $user->password !== null,
            'google_linked' => $user->google_id !== null,
        ];
    }

    /** @return array<string, mixed> */
    public function updateProfile(User $user, array $data): array
    {
        $user->update([
            'name' => trim((string) $data['name']),
            'phone' => GeorgianPhone::normalize((string) $data['phone']),
        ]);

        return $this->userPayload($user->fresh() ?? $user);
    }

    /** Keeps this device signed in and signs out every other one. @return array<string, mixed> */
    public function changePassword(User $user, ?string $current, string $new): array
    {
        $this->assertPassword($user, $current);

        $user->forceFill(['password' => $new])->save();
        $currentTokenId = $user->currentAccessToken()?->getKey();
        $user->tokens()->when($currentTokenId !== null, fn ($query) => $query->whereKeyNot($currentTokenId))->delete();

        return $this->userPayload($user->fresh() ?? $user);
    }

    public function deleteAccount(User $user, ?string $password): void
    {
        $this->assertPassword($user, $password);

        $user->tokens()->delete();
        $user->delete();
    }

    /** Users with a password must confirm it; Google-only users have none to confirm. */
    private function assertPassword(User $user, ?string $password): void
    {
        if ($user->password !== null && ! Hash::check((string) $password, $user->password)) {
            throw new WebApiException('invalid_password', 'The password is incorrect.');
        }
    }

    private function sendCode(string $email, string $purpose, string $locale): void
    {
        $code = $this->codes->issue($email, $purpose);
        Mail::to($email)->queue(new VerificationCodeMail($code, $purpose, self::mailLocale($locale)));
    }

    /** @return array{token: string, user: array<string, mixed>} */
    public function loginWithGoogle(string $idToken): array
    {
        $google = $this->google->verify($idToken);

        $user = User::query()->where('google_id', $google['sub'])->first()
            ?? User::query()->where('email', $google['email'])->first();

        if ($user instanceof User) {
            $changes = ['google_id' => $google['sub']];
            if ($user->email_verified_at === null) {
                // An unconfirmed password account may belong to someone else: Google proves ownership, the old password goes.
                $changes += ['email_verified_at' => now(), 'password' => null];
            }
            $user->forceFill($changes)->save();
        } else {
            $user = User::query()->create([
                'name' => $google['name'] !== '' ? $google['name'] : strstr($google['email'], '@', true),
                'email' => $google['email'],
                'password' => null,
            ]);
            $user->forceFill(['google_id' => $google['sub'], 'email_verified_at' => now()])->save();
        }

        return $this->session($user);
    }
}
