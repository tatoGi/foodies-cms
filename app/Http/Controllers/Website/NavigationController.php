<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\NavigationRequest;
use App\Services\Website\NavigationService;
use Illuminate\Http\JsonResponse;

class NavigationController extends Controller
{
    public function __construct(
        private readonly NavigationService $navigationService,
    ) {}

    public function __invoke(NavigationRequest $request): JsonResponse
    {
        return response()->json($this->navigationService->payload($request->localeCode()));
    }
}
