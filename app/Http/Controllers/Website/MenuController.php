<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\WebsiteMenuRequest;
use App\Services\Website\WebsiteMenuService;
use Illuminate\Http\JsonResponse;

class MenuController extends Controller
{
    public function __construct(private readonly WebsiteMenuService $menu) {}

    public function index(WebsiteMenuRequest $request): JsonResponse
    {
        return response()->json($this->menu->menu($request->localeCode()));
    }

    public function category(WebsiteMenuRequest $request, string $slug): JsonResponse
    {
        $data = $this->menu->category($slug, $request->localeCode());

        abort_if($data === null, 404);

        return response()->json($data);
    }

    public function status(): JsonResponse
    {
        return response()->json($this->menu->status());
    }
}
