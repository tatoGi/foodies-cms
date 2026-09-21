<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\BootstrapRequest;
use App\Services\Website\WebsiteBootstrapService;
use Illuminate\Http\JsonResponse;

class BootstrapController extends Controller
{
    public function __construct(
        private readonly WebsiteBootstrapService $bootstrapService,
    ) {}

    public function __invoke(BootstrapRequest $request): JsonResponse
    {
        return response()->json(
            $this->bootstrapService->bootstrap($request->localeCode())
        );
    }
}
