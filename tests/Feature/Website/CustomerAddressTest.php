<?php

declare(strict_types=1);

namespace Tests\Feature\Website;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAddressTest extends TestCase
{
    use RefreshDatabase;

    private function actingToken(?User $user = null): string
    {
        $user ??= User::factory()->create(['email_verified_at' => now()]);

        return $user->createToken('web', ['*'], now()->addDays(30))->plainTextToken;
    }

    private function address(array $overrides = []): array
    {
        return array_merge([
            'label' => 'სახლი',
            'address_line' => 'ვაჟა-ფშაველას 12',
            'entrance' => '2', 'floor' => '5', 'apartment' => '18',
            'lat' => 41.7251, 'lng' => 44.7461,
            'notes' => 'ზარი არ მუშაობს',
        ], $overrides);
    }

    public function test_the_first_address_becomes_default_and_a_new_default_replaces_it(): void
    {
        $token = $this->actingToken();

        $first = $this->withToken($token)->postJson('/api/web/me/addresses', $this->address())
            ->assertCreated()->assertJsonPath('address.is_default', true)->json('address.id');
        $this->withToken($token)->postJson('/api/web/me/addresses', $this->address(['label' => 'სამსახური', 'is_default' => true]))
            ->assertCreated()->assertJsonPath('address.is_default', true);

        $addresses = $this->withToken($token)->getJson('/api/web/me/addresses')->assertOk()->json('addresses');
        $this->assertCount(2, $addresses);
        $this->assertFalse(collect($addresses)->firstWhere('id', $first)['is_default']);
    }

    public function test_coordinates_outside_georgia_are_rejected(): void
    {
        $this->withToken($this->actingToken())
            ->postJson('/api/web/me/addresses', $this->address(['lat' => 51.5, 'lng' => -0.12]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['lat', 'lng']);
    }

    public function test_someone_elses_address_is_invisible(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $id = $this->withToken($this->actingToken($owner))->postJson('/api/web/me/addresses', $this->address())->json('address.id');
        $this->app['auth']->forgetGuards();

        $intruder = $this->actingToken();
        $this->withToken($intruder)->putJson("/api/web/me/addresses/{$id}", $this->address())->assertNotFound();
        $this->withToken($intruder)->deleteJson("/api/web/me/addresses/{$id}")->assertNotFound();
    }

    public function test_update_and_delete_work_and_the_limit_is_ten(): void
    {
        $token = $this->actingToken();
        $id = $this->withToken($token)->postJson('/api/web/me/addresses', $this->address())->json('address.id');

        $this->withToken($token)->putJson("/api/web/me/addresses/{$id}", $this->address(['label' => 'დედასთან']))
            ->assertOk()->assertJsonPath('address.label', 'დედასთან');
        $this->withToken($token)->deleteJson("/api/web/me/addresses/{$id}")->assertOk();

        for ($i = 0; $i < 10; $i++) {
            $this->withToken($token)->postJson('/api/web/me/addresses', $this->address())->assertCreated();
        }
        $this->withToken($token)->postJson('/api/web/me/addresses', $this->address())
            ->assertStatus(422)->assertJsonPath('code', 'address_limit');
    }
}
