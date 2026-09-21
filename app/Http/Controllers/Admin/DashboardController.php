<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Services\AdminActivityService;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(AdminActivityService $activityService): View
    {
        $totalPages = Page::query()->count();
        $totalPosts = Post::query()->count();
        $totalMedia = Media::query()->count();
        $totalProducts = Product::query()->count();
        $activities = $activityService->recent(5);

        $storagePath = storage_path('app/public');
        $totalBytes = 0;
        if (File::exists($storagePath)) {
            foreach (File::allFiles($storagePath) as $file) {
                $totalBytes += $file->getSize();
            }
        }

        $usedGB = round($totalBytes / (1024 * 1024 * 1024), 2);
        $totalGB = (float) env('DISK_QUOTA_GB', 15);
        $usagePercent = $totalGB > 0 ? min(100, round(($usedGB / $totalGB) * 100)) : 0;

        return view('admin.dashboard', [
            'totalPages'   => $totalPages,
            'totalPosts'   => $totalPosts,
            'totalMedia'   => $totalMedia,
            'totalProducts' => $totalProducts,
            'activities'   => $activities,
            'storage'      => [
                'used'    => $usedGB,
                'total'   => $totalGB,
                'percent' => $usagePercent,
            ],
        ]);
    }
}
