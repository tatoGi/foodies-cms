<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\AdminOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly AdminOrderService $adminOrderService,
    ) {}

    public function index(Request $request): View
    {
        return view('admin.orders.index', $this->adminOrderService->list($request->only([
            'search',
            'status',
        ])));
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $this->adminOrderService->updateStatus($order, (string) $request->validated('status'));

        return back()->with('success', __('Order status updated successfully.'));
    }
}
