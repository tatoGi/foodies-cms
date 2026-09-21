<?php

declare(strict_types=1);

namespace Tests\Feature\Pos;

use App\Models\PosDevice;
use App\Services\Pos\PosDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PosHeartbeatTest extends TestCase
{
    use RefreshDatabase;

    /** @var array{device: PosDevice, token: string, secret: string} */
    private array $credentials;

    protected function setUp(): void
    {
        parent::setUp();

        $this->credentials = app(PosDeviceService::class)->register('Test POS');
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $overrides  headers to replace (token, timestamp, signature)
     */
    private function signedPost(string $uri, array $payload, array $overrides = []): TestResponse
    {
        $body = json_encode($payload);
        $timestamp = $overrides['timestamp'] ?? (string) time();
        $secret = $overrides['secret'] ?? $this->credentials['secret'];

        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_TIMESTAMP' => $timestamp,
            'HTTP_X_SIGNATURE' => $overrides['signature'] ?? hash_hmac('sha256', $timestamp.'.'.$body, $secret),
        ];

        $token = $overrides['token'] ?? $this->credentials['token'];
        if ($token !== '') {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        return $this->call('POST', $uri, [], [], [], $server, $body);
    }

    public function test_valid_heartbeat_records_device_status(): void
    {
        $this->signedPost('/api/pos/v1/heartbeat', [
            'app_version' => '1.4.0',
            'kitchen_enabled' => true,
            'open' => true,
            'queue_depth' => 3,
        ])->assertOk()->assertJsonPath('ok', true);

        $device = $this->credentials['device']->fresh();
        $this->assertNotNull($device->last_seen_at);
        $this->assertSame('1.4.0', $device->app_version);
        $this->assertSame(3, $device->status['queue_depth']);
    }

    public function test_request_without_token_is_rejected(): void
    {
        $this->signedPost('/api/pos/v1/heartbeat', ['app_version' => '1'], ['token' => ''])
            ->assertUnauthorized();
    }

    public function test_request_with_unknown_token_is_rejected(): void
    {
        $this->signedPost('/api/pos/v1/heartbeat', ['app_version' => '1'], ['token' => 'nope'])
            ->assertUnauthorized();
    }

    public function test_request_with_wrong_signature_is_rejected(): void
    {
        $this->signedPost('/api/pos/v1/heartbeat', ['app_version' => '1'], ['secret' => 'wrong-secret'])
            ->assertUnauthorized();
    }

    public function test_request_with_stale_timestamp_is_rejected(): void
    {
        $this->signedPost('/api/pos/v1/heartbeat', ['app_version' => '1'], ['timestamp' => (string) (time() - 3600)])
            ->assertUnauthorized();
    }

    public function test_inactive_device_is_rejected(): void
    {
        $this->credentials['device']->update(['is_active' => false]);

        $this->signedPost('/api/pos/v1/heartbeat', ['app_version' => '1'])->assertUnauthorized();
    }

    public function test_heartbeat_requires_app_version(): void
    {
        $this->signedPost('/api/pos/v1/heartbeat', [])->assertUnprocessable()->assertJsonValidationErrors('app_version');
    }
}
