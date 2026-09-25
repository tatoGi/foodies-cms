<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Models\PosDailyProductSale;
use App\Models\PosDailySale;
use App\Models\PosDevice;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Stores the POS's daily sales totals. Each sent day fully replaces what the CMS had for it,
 * so re-sending is harmless and a corrected day (e.g. a cancelled bill) overwrites the old figures.
 */
class PosSalesSyncService
{
    /**
     * @param  list<array<string, mixed>>  $days
     * @return int number of days stored
     */
    public function apply(PosDevice $device, array $days): int
    {
        $externalIds = collect($days)->flatMap(fn (array $day): array => array_column($day['products'], 'external_id'))->unique();
        $productIds = Product::query()
            ->where('external_source', 'pos')
            ->whereIn('external_id', $externalIds)
            ->pluck('id', 'external_id');

        DB::transaction(function () use ($device, $days, $productIds): void {
            foreach ($days as $day) {
                // The date cast stores "Y-m-d 00:00:00", so match the day with whereDate rather than updateOrCreate.
                $row = PosDailySale::query()
                    ->where('pos_device_id', $device->id)
                    ->whereDate('sales_date', $day['date'])
                    ->first() ?? new PosDailySale(['pos_device_id' => $device->id, 'sales_date' => $day['date']]);
                $row->fill([
                    'bills' => (int) $day['bills'],
                    'gross' => $day['gross'],
                    'discount' => $day['discount'],
                    'net' => $day['net'],
                    'cash' => $day['cash'],
                    'card' => $day['card'],
                    'other' => $day['other'],
                ])->save();

                PosDailyProductSale::query()
                    ->where('pos_device_id', $device->id)
                    ->whereDate('sales_date', $day['date'])
                    ->delete();

                foreach ($day['products'] as $row) {
                    PosDailyProductSale::query()->create([
                        'pos_device_id' => $device->id,
                        'sales_date' => $day['date'],
                        'external_id' => (int) $row['external_id'],
                        'product_id' => $productIds[(int) $row['external_id']] ?? null,
                        'title' => $row['title'],
                        'quantity' => (int) $row['quantity'],
                        'revenue' => $row['revenue'],
                    ]);
                }
            }
        });

        return count($days);
    }
}
