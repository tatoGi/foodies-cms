<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PosSalesRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/** Sales statistics for the admin: in-store sales (POS daily totals) and online orders together. */
class AdminSalesService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly PosSalesRepositoryInterface $posSales,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function statistics(string $range = 'year'): array
    {
        $dateFrom = $this->dateFrom($range);
        $prevFrom = $this->prevPeriodFrom($range);
        $prevTo = $dateFrom;

        $store = $this->posSales->totals($dateFrom);
        $onlineRevenue = $this->orderRepository->revenueForCompleted($dateFrom);
        $onlineOrders = $this->orderRepository->countOrders($dateFrom);
        $totalRevenue = $store['net'] + $onlineRevenue;
        $totalOrders = $store['bills'] + $onlineOrders;

        $prevRevenue = 0.0;
        $prevOrders = 0;
        if ($prevFrom) {
            $prevStore = $this->posSales->totals($prevFrom, $prevTo);
            $prevRevenue = $prevStore['net'] + $this->orderRepository->revenueForCompleted($prevFrom, $prevTo);
            $prevOrders = $prevStore['bills'] + $this->orderRepository->countOrders($prevFrom, $prevTo);
        }

        $categoryRevenue = $this->orderRepository->categoryRevenue($dateFrom);

        return [
            'range' => $range,
            'totalRevenue' => $totalRevenue,
            'inStoreRevenue' => $store['net'],
            'onlineRevenue' => $onlineRevenue,
            'totalOrders' => $totalOrders,
            'inStoreOrders' => $store['bills'],
            'onlineOrders' => $onlineOrders,
            'productsSold' => $this->posSales->dishesSold($dateFrom) + $this->orderRepository->productsSold($dateFrom),
            'avgOrderValue' => $totalOrders > 0 ? $totalRevenue / $totalOrders : 0.0,
            'paymentSplit' => [
                'cash' => $store['cash'],
                'card' => $store['card'],
                'other' => $store['other'],
                'online' => $onlineRevenue,
            ],
            'revenueGrowth' => $prevRevenue > 0 ? round((($totalRevenue - $prevRevenue) / $prevRevenue) * 100, 1) : null,
            'ordersGrowth' => $prevOrders > 0 ? round((($totalOrders - $prevOrders) / $prevOrders) * 100, 1) : null,
            'monthlySales' => $this->monthlySales(),
            'topProducts' => $this->topProducts($dateFrom),
            'ordersByStatus' => $this->orderRepository->ordersByStatus($dateFrom),
            'recentOrders' => $this->orderRepository->recentOrders(),
            'categoryRevenue' => $categoryRevenue,
            'maxCategoryRevenue' => $categoryRevenue->max('revenue') ?: 1,
        ];
    }

    /** Dishes from both channels, merged by name. */
    private function topProducts(?Carbon $dateFrom): Collection
    {
        $online = $this->orderRepository->topProducts($dateFrom)->map(fn ($row): array => [
            'product_name' => (string) $row->product_name,
            'total_qty' => (int) $row->total_qty,
            'total_revenue' => (float) $row->total_revenue,
        ]);

        return $this->posSales->topProducts($dateFrom)
            ->concat($online)
            ->groupBy('product_name')
            ->map(fn (Collection $rows, string $name): array => [
                'product_name' => $name,
                'total_qty' => (int) $rows->sum('total_qty'),
                'total_revenue' => (float) $rows->sum('total_revenue'),
            ])
            ->sortByDesc('total_qty')
            ->take(10)
            ->values();
    }

    private function dateFrom(string $range): ?Carbon
    {
        return match ($range) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => null,
        };
    }

    private function prevPeriodFrom(string $range): ?Carbon
    {
        return match ($range) {
            'today' => now()->subDay()->startOfDay(),
            'week' => now()->subWeek()->startOfWeek(),
            'month' => now()->subMonth()->startOfMonth(),
            'quarter' => now()->subQuarter()->startOfQuarter(),
            'year' => now()->subYear()->startOfYear(),
            default => null,
        };
    }

    /**
     * @return array{labels: list<string>, revenue: list<float>, inStore: list<float>, online: list<float>, orders: list<int>}
     */
    private function monthlySales(): array
    {
        $online = $this->orderRepository->monthlySales();
        $store = $this->posSales->monthly();
        $result = ['labels' => [], 'revenue' => [], 'inStore' => [], 'online' => [], 'orders' => []];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $key = $month->format('Y-m');
            $storeRevenue = (float) ($store[$key]['revenue'] ?? 0);
            $onlineRevenue = (float) ($online[$key]->revenue ?? 0);

            $result['labels'][] = $month->format('M Y');
            $result['inStore'][] = $storeRevenue;
            $result['online'][] = $onlineRevenue;
            $result['revenue'][] = $storeRevenue + $onlineRevenue;
            $result['orders'][] = (int) ($store[$key]['bills'] ?? 0) + (int) ($online[$key]->orders ?? 0);
        }

        return $result;
    }
}
