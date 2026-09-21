<?php

declare(strict_types=1);

namespace Tests\Feature\Pos;

use App\Models\PosDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosDeviceCreateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_a_device_and_prints_working_credentials_once(): void
    {
        $this->artisan('pos:device-create', ['name' => 'Bakery POS'])
            ->expectsOutputToContain('Token:')
            ->expectsOutputToContain('Secret:')
            ->assertSuccessful();

        $device = PosDevice::sole();
        $this->assertSame('Bakery POS', $device->name);
        $this->assertTrue($device->is_active);
    }
}
