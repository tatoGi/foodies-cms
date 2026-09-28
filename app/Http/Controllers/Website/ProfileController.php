<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\PasswordChangeRequest;
use App\Http\Requests\Website\ProfileUpdateRequest;
use App\Services\Website\WebsiteAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private readonly WebsiteAuthService $auth,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->auth->userPayload($request->user())]);
    }

    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        return response()->json(['user' => $this->auth->updateProfile($request->user(), $request->validated())]);
    }

    public function password(PasswordChangeRequest $request): JsonResponse
    {
        return response()->json(['user' => $this->auth->changePassword(
            $request->user(),
            $request->input('current_password'),
            (string) $request->input('password'),
        )]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->auth->deleteAccount($request->user(), $request->input('password'));

        return response()->json(['success' => true]);
    }
}
