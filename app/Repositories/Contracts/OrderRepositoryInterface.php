<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface OrderRepositoryInterface
{
    public function revenueForCompleted(?CarbonInterface $dateFrom = null, ?CarbonInterface $dateTo = null): float;

    public function countOrders(?CarbonInterface $dateFrom = null, ?CarbonInterface $dateTo = null): int;

    public function productsSold(?CarbonInterface $dateFrom = null): int;

    public function monthlySales(int $months = 12): Collection;

    public function topProducts(?CarbonInterface $dateFrom = null, int $limit = 10): Collection;

    /**
     * @return array<string, int>
     */
    public function ordersByStatus(?CarbonInterface $dateFrom = null): array;

    public function recentOrders(int $limit = 10): Collection;

    public function categoryRevenue(?CarbonInterface $dateFrom = null): Collection;

    public function paginateForAdmin(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function updateStatus(Order $order, string $status): void;
}
