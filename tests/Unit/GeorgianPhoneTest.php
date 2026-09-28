<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\GeorgianPhone;
use PHPUnit\Framework\TestCase;

class GeorgianPhoneTest extends TestCase
{
    public function test_it_normalizes_common_georgian_mobile_formats(): void
    {
        $this->assertSame('+995577422942', GeorgianPhone::normalize('577 42 29 42'));
        $this->assertSame('+995577422942', GeorgianPhone::normalize('+995 577-42-29-42'));
        $this->assertSame('+995577422942', GeorgianPhone::normalize('995577422942'));
    }

    public function test_it_rejects_non_mobile_or_malformed_numbers(): void
    {
        $this->assertNull(GeorgianPhone::normalize('032 2 00 00 00'));
        $this->assertNull(GeorgianPhone::normalize('57742294'));
        $this->assertNull(GeorgianPhone::normalize(''));
        $this->assertNull(GeorgianPhone::normalize(null));
    }
}
