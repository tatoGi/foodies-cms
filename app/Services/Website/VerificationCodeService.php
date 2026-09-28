<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\VerificationCode;

/** One-time 6-digit e-mail codes: stored as HMAC, valid 15 minutes, burnt after 5 wrong tries. */
class VerificationCodeService
{
    public const PURPOSE_VERIFY = 'verify_email';

    public const PURPOSE_RESET = 'reset_password';

    private const TTL_MINUTES = 15;

    private const MAX_ATTEMPTS = 5;

    public function issue(string $email, string $purpose): string
    {
        $email = strtolower(trim($email));
        VerificationCode::query()->where('email', $email)->where('purpose', $purpose)->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        VerificationCode::query()->create([
            'email' => $email,
            'purpose' => $purpose,
            'code_hash' => $this->hash($code),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
        ]);

        return $code;
    }

    public function consume(string $email, string $purpose, string $code): bool
    {
        $row = VerificationCode::query()
            ->where('email', strtolower(trim($email)))
            ->where('purpose', $purpose)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();

        if (! $row instanceof VerificationCode || $row->expires_at->isPast() || $row->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! hash_equals($row->code_hash, $this->hash(trim($code)))) {
            $row->increment('attempts');

            return false;
        }

        $row->forceFill(['consumed_at' => now()])->save();

        return true;
    }

    private function hash(string $code): string
    {
        return hash_hmac('sha256', $code, (string) config('app.key'));
    }
}
