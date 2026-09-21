<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminActivityService;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __invoke(AdminActivityService $activityService): View
    {
        return view('admin.activity-logs.index', [
            'activities' => $activityService->paginate(25),
        ]);
    }
}
