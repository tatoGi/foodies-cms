<?php

declare(strict_types=1);

namespace Tests\Feature\Website;

use App\Services\Website\VerificationCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    private function codes(): VerificationCodeService
    {
        return app(VerificationCodeService::class);
    }

    public function test_a_code_is_six_digits_and_works_once(): void
    {
        $code = $this->codes()->issue('nino@example.com', VerificationCodeService::PURPOSE_VERIFY);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertTrue($this->codes()->consume('nino@example.com', VerificationCodeService::PURPOSE_VERIFY, $code));
        $this->assertFalse($this->codes()->consume('nino@example.com', VerificationCodeService::PURPOSE_VERIFY, $code));
    }

    public function test_the_code_is_bound_to_email_and_purpose(): void
    {
        $code = $this->codes()->issue('nino@example.com', VerificationCodeService::PURPOSE_VERIFY);

        $this->assertFalse($this->codes()->consume('other@example.com', VerificationCodeService::PURPOSE_VERIFY, $code));
        $this->assertFalse($this->codes()->consume('nino@example.com', VerificationCodeService::PURPOSE_RESET, $code));
    }

    public function test_the_code_expires_after_fifteen_minutes(): void
    {
        $code = $this->codes()->issue('nino@example.com', VerificationCodeService::PURPOSE_VERIFY);
        $this->travel(16)->minutes();

        $this->assertFalse($this->codes()->consume('nino@example.com', VerificationCodeService::PURPOSE_VERIFY, $code));
    }

    public function test_five_wrong_attempts_burn_the_code(): void
    {
        $code = $this->codes()->issue('nino@example.com', VerificationCodeService::PURPOSE_VERIFY);
        $wrong = $code === '000000' ? '111111' : '000000';
        for ($i = 0; $i < 5; $i++) {
            $this->codes()->consume('nino@example.com', VerificationCodeService::PURPOSE_VERIFY, $wrong);
        }

        $this->assertFalse($this->codes()->consume('nino@example.com', VerificationCodeService::PURPOSE_VERIFY, $code));
    }

    public function test_issuing_a_new_code_replaces_the_old_one(): void
    {
        $first = $this->codes()->issue('nino@example.com', VerificationCodeService::PURPOSE_VERIFY);
        $second = $this->codes()->issue('nino@example.com', VerificationCodeService::PURPOSE_VERIFY);

        if ($first !== $second) {
            $this->assertFalse($this->codes()->consume('nino@example.com', VerificationCodeService::PURPOSE_VERIFY, $first));
        }
        $this->assertTrue($this->codes()->consume('nino@example.com', VerificationCodeService::PURPOSE_VERIFY, $second));
    }
}
