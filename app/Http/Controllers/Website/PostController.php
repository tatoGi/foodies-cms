<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Services\Website\WebsitePostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(
        private readonly WebsitePostService $postService,
    ) {}

    public function show(Request $request, string $slug): JsonResponse
    {
        $locale = strtolower(trim((string) $request->query('locale', ''))) ?: app()->getLocale();
        $data = $this->postService->buildPostData($slug, $locale);

        abort_if($data === null, 404);

        return response()->json($data);
    }
}
