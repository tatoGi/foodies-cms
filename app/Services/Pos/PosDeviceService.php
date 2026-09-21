<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Models\PosDevice;
use Illuminate\Support\Str;

class PosDeviceService
{
    /**
     * Create a device and return its credentials. The plain token and secret are only available here.
     *
     * @return array{device: PosDevice, token: string, secret: string}
     */
    public function register(string $name): array
    {
        $token = Str::random(48);
        $secret = Str::random(64);

        $device = PosDevice::query()->create([
            'name' => $name,
            'token_hash' => hash('sha256', $token),
            'signing_secret' => $secret,
        ]);

        return ['device' => $device, 'token' => $token, 'secret' => $secret];
    }

    public function findActiveByToken(string $token): ?PosDevice
    {
        return PosDevice::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('is_active', true)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $status
     */
    public function recordHeartbeat(PosDevice $device, string $appVersion, array $status): void
    {
        $device->update([
            'app_version' => $appVersion,
            'status' => $status,
            'last_seen_at' => now(),
        ]);
    }
}
