<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\AuthLoginRequest;
use App\Http\Requests\Website\AuthRegisterRequest;
use App\Http\Requests\Website\AuthUpdateProfileRequest;
use App\Services\Website\WebsiteAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly WebsiteAuthService $websiteAuthService,
    ) {}

    public function register(AuthRegisterRequest $request): JsonResponse
    {
        return response()->json(
            $this->websiteAuthService->register($request->validated()),
            201,
        );
    }

    public function login(AuthLoginRequest $request): JsonResponse
    {
        return response()->json(
            $this->websiteAuthService->login($request->validated()),
        );
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->websiteAuthService->userPayload($request->user()),
        ]);
    }

    public function update(AuthUpdateProfileRequest $request): JsonResponse
    {
        return response()->json([
            'user' => $this->websiteAuthService->updateProfile($request->user(), $request->validated()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->websiteAuthService->logout($request->user());

        return response()->json([
            'success' => true,
        ]);
    }
}
