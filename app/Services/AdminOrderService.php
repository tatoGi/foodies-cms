<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;

class AdminOrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    /**
     * @return array{orders:\Illuminate\Contracts\Pagination\LengthAwarePaginator,filters:array{search:string,status:string},statusOptions:array<int,string>}
     */
    public function list(array $filters = []): array
    {
        $resolved = [
            'search' => trim((string) ($filters['search'] ?? '')),
            'status' => trim((string) ($filters['status'] ?? '')),
        ];

        return [
            'orders' => $this->orderRepository->paginateForAdmin($resolved),
            'filters' => $resolved,
            'statusOptions' => [
                Order::STATUS_PENDING_PAYMENT,
                Order::STATUS_PAID,
                Order::STATUS_PROCESSING,
                Order::STATUS_COMPLETED,
                Order::STATUS_CANCELLED,
            ],
        ];
    }

    public function updateStatus(Order $order, string $status): void
    {
        $this->orderRepository->updateStatus($order, $status);
    }
}
