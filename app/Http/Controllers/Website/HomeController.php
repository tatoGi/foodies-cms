<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Services\Website\HomepageService;
use Illuminate\Http\JsonResponse;

class HomeController extends Controller
{
    public function __construct(
        private readonly HomepageService $homepageService,
    ) {}

    public function __invoke(): JsonResponse
    {
        $payload = $this->homepageService->payload();

        return response()->json($payload);
    }
}
