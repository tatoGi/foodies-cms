<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\PosDailyProductSale;
use App\Models\PosDailySale;
use App\Services\AdminSalesService;
use App\Services\Pos\PosDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_in_store_sales_are_combined_with_online_orders(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 25)->setTime(15, 0));
        $device = app(PosDeviceService::class)->register('Bakery')['device'];
        foreach (['2026-09-24' => 100, '2026-09-25' => 150] as $date => $net) {
            PosDailySale::query()->create([
                'pos_device_id' => $device->id, 'sales_date' => $date, 'bills' => 10,
                'gross' => $net, 'discount' => 0, 'net' => $net, 'cash' => $net - 40, 'card' => 40, 'other' => 0,
            ]);
            PosDailyProductSale::query()->create([
                'pos_device_id' => $device->id, 'sales_date' => $date, 'external_id' => 8,
                'title' => 'ქათმის შაურმა', 'quantity' => 6, 'revenue' => 72,
            ]);
        }
        PosDailySale::query()->create([
            'pos_device_id' => $device->id, 'sales_date' => '2026-08-01', 'bills' => 3,
            'gross' => 30, 'discount' => 0, 'net' => 30, 'cash' => 30, 'card' => 0, 'other' => 0,
        ]);

        $stats = app(AdminSalesService::class)->statistics('month');

        $this->assertSame(250.0, $stats['inStoreRevenue']);
        $this->assertSame(0.0, $stats['onlineRevenue']);
        $this->assertSame(250.0, $stats['totalRevenue']);
        $this->assertSame(20, $stats['totalOrders']);
        $this->assertSame(12.5, $stats['avgOrderValue']);
        $this->assertSame(12, $stats['productsSold']);
        $this->assertSame(['cash' => 170.0, 'card' => 80.0, 'other' => 0.0, 'online' => 0.0], $stats['paymentSplit']);
        $this->assertSame('ქათმის შაურმა', $stats['topProducts']->first()['product_name']);
        $this->assertSame(12, $stats['topProducts']->first()['total_qty']);

        $today = app(AdminSalesService::class)->statistics('today');
        $this->assertSame(150.0, $today['totalRevenue']);

        $this->actingAs($this->createAdminUser(), 'admin')
            ->get(route('admin.sales.index', ['range' => 'month']))
            ->assertOk()
            ->assertSee('250.00')
            ->assertSee(__('In store'));
    }
}
