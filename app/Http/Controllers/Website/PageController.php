<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\PageShowRequest;
use App\Services\Website\WebsitePageService;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    public function __construct(
        private readonly WebsitePageService $pageService,
    ) {}

    public function show(PageShowRequest $request): JsonResponse
    {
        $data = $this->pageService->buildPageData($request->slug(), $request->localeCode());

        abort_if($data === null, 404);

        return response()->json($data);
    }
}
