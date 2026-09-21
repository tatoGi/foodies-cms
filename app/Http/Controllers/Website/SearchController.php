<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Services\Website\WebsiteSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        private readonly WebsiteSearchService $searchService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $locale = $request->query('locale');

        return response()->json(
            $this->searchService->search($query, $locale ?: null)
        );
    }
}
