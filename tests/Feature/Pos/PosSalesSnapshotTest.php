<?php

declare(strict_types=1);

namespace Tests\Feature\Pos;

use App\Models\PosDailyProductSale;
use App\Models\PosDailySale;
use App\Models\PosDevice;
use App\Models\Product;
use App\Services\Pos\PosDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PosSalesSnapshotTest extends TestCase
{
    use RefreshDatabase;

    /** @var array{device: PosDevice, token: string, secret: string} */
    private array $credentials;

    protected function setUp(): void
    {
        parent::setUp();

        $this->credentials = app(PosDeviceService::class)->register('Test POS');
    }

    public function test_stores_a_day_with_its_dishes(): void
    {
        $product = Product::query()->create([
            'external_source' => 'pos', 'external_id' => 8, 'sku' => 'pos-8', 'price' => 12,
            'sort_order' => 1, 'block_types' => [], 'is_active' => true,
        ]);

        $this->postSales([$this->day('2026-09-25', 245, [[8, 5, 60], [9, 2, 28]])])
            ->assertOk()
            ->assertJsonPath('days', 1);

        $day = PosDailySale::query()->sole();
        $this->assertSame('2026-09-25', $day->sales_date->toDateString());
        $this->assertSame(12, $day->bills);
        $this->assertSame('245.00', $day->net);
        $this->assertSame('100.00', $day->cash);
        $dish = PosDailyProductSale::query()->where('external_id', 8)->sole();
        $this->assertSame($product->id, $dish->product_id);
        $this->assertSame(5, $dish->quantity);
        $this->assertNull(PosDailyProductSale::query()->where('external_id', 9)->sole()->product_id);
    }

    public function test_resending_a_day_replaces_it(): void
    {
        $this->postSales([$this->day('2026-09-25', 245, [[8, 5, 60], [9, 2, 28]])])->assertOk();
        $this->postSales([$this->day('2026-09-25', 300, [[8, 7, 84]])])->assertOk();

        $this->assertSame(1, PosDailySale::query()->count());
        $this->assertSame('300.00', PosDailySale::query()->sole()->net);
        $this->assertSame([8 => 7], PosDailyProductSale::query()->pluck('quantity', 'external_id')->all());

        $this->postSales([$this->day('2026-09-25', 0, [])])->assertOk();
        $this->assertSame(0, PosDailyProductSale::query()->count());
    }

    public function test_rejects_unsigned_and_invalid_requests(): void
    {
        $this->postSales([$this->day('2026-09-25', 10, [])], signed: false)->assertUnauthorized();
        $this->postSales([['date' => 'yesterday', 'bills' => -1]])->assertUnprocessable();
    }

    /** @param  list<array{0: int, 1: int, 2: float}>  $dishes */
    private function day(string $date, float $net, array $dishes): array
    {
        return [
            'date' => $date,
            'bills' => $net > 0 ? 12 : 0,
            'gross' => number_format($net + 5, 2, '.', ''),
            'discount' => $net > 0 ? '5.00' : '0.00',
            'net' => number_format($net, 2, '.', ''),
            'cash' => $net > 0 ? '100.00' : '0.00',
            'card' => number_format(max($net - 100, 0), 2, '.', ''),
            'other' => '0.00',
            'products' => array_map(fn (array $d): array => [
                'external_id' => $d[0], 'title' => "Dish {$d[0]}", 'quantity' => $d[1], 'revenue' => number_format($d[2], 2, '.', ''),
            ], $dishes),
        ];
    }

    /** @param  list<array<string, mixed>>  $days */
    private function postSales(array $days, bool $signed = true): TestResponse
    {
        $body = json_encode(['days' => $days]);
        $timestamp = (string) time();

        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        if ($signed) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$this->credentials['token'];
            $server['HTTP_X_TIMESTAMP'] = $timestamp;
            $server['HTTP_X_SIGNATURE'] = hash_hmac('sha256', $timestamp.'.'.$body, $this->credentials['secret']);
        }

        return $this->call('POST', '/api/pos/v1/sync/sales', [], [], [], $server, $body);
    }
}
