<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminSalesService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesStatisticsController extends Controller
{
    public function __construct(
        private readonly AdminSalesService $adminSalesService,
    ) {}

    public function __invoke(Request $request): View
    {
        return view('admin.sales.index', $this->adminSalesService->statistics(
            (string) $request->query('range', 'year'),
        ));
    }
}
