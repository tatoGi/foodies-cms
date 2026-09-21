<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\Contracts\OrderRepositoryInterface;

class AdminSalesService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function statistics(string $range = 'year'): array
    {
        $dateFrom = $this->dateFrom($range);
        $prevFrom = $this->prevPeriodFrom($range);
        $prevTo = $dateFrom;

        $totalRevenue = $this->orderRepository->revenueForCompleted($dateFrom);
        $totalOrders = $this->orderRepository->countOrders($dateFrom);
        $productsSold = $this->orderRepository->productsSold($dateFrom);
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0.0;

        $prevRevenue = $prevFrom ? $this->orderRepository->revenueForCompleted($prevFrom, $prevTo) : 0.0;
        $prevOrders = $prevFrom ? $this->orderRepository->countOrders($prevFrom, $prevTo) : 0;

        $monthlyRows = $this->orderRepository->monthlySales();
        $monthlySales = $this->normalizeMonthlySales($monthlyRows);
        $categoryRevenue = $this->orderRepository->categoryRevenue($dateFrom);

        return [
            'range' => $range,
            'totalRevenue' => $totalRevenue,
            'totalOrders' => $totalOrders,
            'productsSold' => $productsSold,
            'avgOrderValue' => $avgOrderValue,
            'revenueGrowth' => $prevRevenue > 0 ? round((($totalRevenue - $prevRevenue) / $prevRevenue) * 100, 1) : null,
            'ordersGrowth' => $prevOrders > 0 ? round((($totalOrders - $prevOrders) / $prevOrders) * 100, 1) : null,
            'monthlySales' => $monthlySales,
            'topProducts' => $this->orderRepository->topProducts($dateFrom),
            'ordersByStatus' => $this->orderRepository->ordersByStatus($dateFrom),
            'recentOrders' => $this->orderRepository->recentOrders(),
            'categoryRevenue' => $categoryRevenue,
            'maxCategoryRevenue' => $categoryRevenue->max('revenue') ?: 1,
        ];
    }

    private function dateFrom(string $range): ?\Carbon\Carbon
    {
        return match ($range) {
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => null,
        };
    }

    private function prevPeriodFrom(string $range): ?\Carbon\Carbon
    {
        return match ($range) {
            'month' => now()->subMonth()->startOfMonth(),
            'quarter' => now()->subQuarter()->startOfQuarter(),
            'year' => now()->subYear()->startOfYear(),
            default => null,
        };
    }

    /**
     * @param  \Illuminate\Support\Collection<string, object>  $rows
     * @return array{labels:array<int, string>,revenue:array<int, float>,orders:array<int, int>}
     */
    private function normalizeMonthlySales($rows): array
    {
        $labels = [];
        $revenue = [];
        $orders = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->format('M Y');
            $revenue[] = (float) ($rows[$key]->revenue ?? 0);
            $orders[] = (int) ($rows[$key]->orders ?? 0);
        }

        return compact('labels', 'revenue', 'orders');
    }
}
