<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Models\OrderItem;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderRepository implements OrderRepositoryInterface
{
    public function revenueForCompleted(?CarbonInterface $dateFrom = null, ?CarbonInterface $dateTo = null): float
    {
        return (float) Order::query()
            ->revenueRelevant()
            ->when($dateFrom && $dateTo, fn ($query) => $query->whereBetween('ordered_at', [$dateFrom, $dateTo]))
            ->when($dateFrom && ! $dateTo, fn ($query) => $query->where('ordered_at', '>=', $dateFrom))
            ->sum('total');
    }

    public function countOrders(?CarbonInterface $dateFrom = null, ?CarbonInterface $dateTo = null): int
    {
        return Order::query()
            ->when($dateFrom && $dateTo, fn ($query) => $query->whereBetween('ordered_at', [$dateFrom, $dateTo]))
            ->when($dateFrom && ! $dateTo, fn ($query) => $query->where('ordered_at', '>=', $dateFrom))
            ->count();
    }

    public function productsSold(?CarbonInterface $dateFrom = null): int
    {
        return (int) OrderItem::query()
            ->whereHas('order', function ($query) use ($dateFrom): void {
                $query->revenueRelevant()
                    ->when($dateFrom, fn ($q) => $q->where('ordered_at', '>=', $dateFrom));
            })
            ->sum('quantity');
    }

    public function monthlySales(int $months = 12): Collection
    {
        return Order::query()
            ->select(
                DB::raw("DATE_FORMAT(ordered_at, '%Y-%m') as month"),
                DB::raw("SUM(CASE WHEN status = 'completed' THEN total ELSE 0 END) as revenue"),
                DB::raw('COUNT(*) as orders')
            )
            ->where('ordered_at', '>=', now()->subMonths($months - 1)->startOfMonth())
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');
    }

    public function topProducts(?CarbonInterface $dateFrom = null, int $limit = 10): Collection
    {
        return OrderItem::query()
            ->select(
                'product_name',
                DB::raw('SUM(quantity) as total_qty'),
                DB::raw('SUM(quantity * unit_price) as total_revenue')
            )
            ->whereHas('order', function ($query) use ($dateFrom): void {
                $query->revenueRelevant()
                    ->when($dateFrom, fn ($q) => $q->where('ordered_at', '>=', $dateFrom));
            })
            ->groupBy('product_name')
            ->orderByDesc('total_qty')
            ->limit($limit)
            ->get();
    }

    public function ordersByStatus(?CarbonInterface $dateFrom = null): array
    {
        return Order::query()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->when($dateFrom, fn ($query) => $query->where('ordered_at', '>=', $dateFrom))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->map(fn ($count) => (int) $count)
            ->toArray();
    }

    public function recentOrders(int $limit = 10): Collection
    {
        return Order::query()
            ->withCount('items')
            ->latest('ordered_at')
            ->limit($limit)
            ->get();
    }

    public function categoryRevenue(?CarbonInterface $dateFrom = null): Collection
    {
        return OrderItem::query()
            ->select(
                DB::raw('COALESCE(products.category, "Uncategorized") as category'),
                DB::raw('SUM(order_items.quantity * order_items.unit_price) as revenue')
            )
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->whereHas('order', function ($query) use ($dateFrom): void {
                $query->revenueRelevant()
                    ->when($dateFrom, fn ($q) => $q->where('ordered_at', '>=', $dateFrom));
            })
            ->groupBy('category')
            ->orderByDesc('revenue')
            ->get();
    }

    public function paginateForAdmin(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        return Order::query()
            ->with(['items'])
            ->withCount('items')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%")
                        ->orWhere('delivery_address', 'like', "%{$search}%");

                    if (is_numeric($search)) {
                        $subQuery->orWhere('id', (int) $search);
                    }
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('ordered_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function updateStatus(Order $order, string $status): void
    {
        $order->update(['status' => $status]);
    }
}
