<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\PosDailyProductSale;
use App\Models\PosDailySale;
use App\Repositories\Contracts\PosSalesRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class PosSalesRepository implements PosSalesRepositoryInterface
{
    public function totals(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $row = $this->between(PosDailySale::query(), $from, $to)
            ->selectRaw('COALESCE(SUM(bills), 0) as bills, COALESCE(SUM(net), 0) as net, COALESCE(SUM(cash), 0) as cash')
            ->selectRaw('COALESCE(SUM(card), 0) as card, COALESCE(SUM(other), 0) as other')
            ->first();

        return [
            'bills' => (int) $row->bills,
            'net' => (float) $row->net,
            'cash' => (float) $row->cash,
            'card' => (float) $row->card,
            'other' => (float) $row->other,
        ];
    }

    public function dishesSold(?CarbonInterface $from = null): int
    {
        return (int) $this->between(PosDailyProductSale::query(), $from)->sum('quantity');
    }

    public function topProducts(?CarbonInterface $from = null, int $limit = 10): Collection
    {
        return $this->between(PosDailyProductSale::query(), $from)
            ->selectRaw('title as product_name, SUM(quantity) as total_qty, SUM(revenue) as total_revenue')
            ->groupBy('title')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get()
            ->map(fn ($row): array => [
                'product_name' => (string) $row->product_name,
                'total_qty' => (int) $row->total_qty,
                'total_revenue' => (float) $row->total_revenue,
            ]);
    }

    public function monthly(int $months = 12): array
    {
        return PosDailySale::query()
            ->whereDate('sales_date', '>=', now()->subMonths($months - 1)->startOfMonth()->toDateString())
            ->get(['sales_date', 'net', 'bills'])
            ->groupBy(fn (PosDailySale $day): string => $day->sales_date->format('Y-m'))
            ->map(fn (Collection $days): array => [
                'revenue' => (float) $days->sum('net'),
                'bills' => (int) $days->sum('bills'),
            ])
            ->all();
    }

    private function between(Builder $query, ?CarbonInterface $from = null, ?CarbonInterface $to = null): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate('sales_date', '>=', $from->toDateString()))
            ->when($to, fn (Builder $q) => $q->whereDate('sales_date', '<', $to->toDateString()));
    }
}
