<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\AuthLoginRequest;
use App\Http\Requests\Website\AuthRegisterRequest;
use App\Http\Requests\Website\EmailOnlyRequest;
use App\Http\Requests\Website\VerifyEmailRequest;
use App\Services\Website\WebsiteAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly WebsiteAuthService $auth,
    ) {}

    public function register(AuthRegisterRequest $request): JsonResponse
    {
        $this->auth->register($request->validated(), (string) $request->input('locale', 'ka'));

        return response()->json(['status' => 'verification_sent'], 202);
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        return response()->json($this->auth->verifyEmail((string) $request->input('email'), (string) $request->input('code')));
    }

    public function resendVerification(EmailOnlyRequest $request): JsonResponse
    {
        $this->auth->resendVerification((string) $request->input('email'), (string) $request->input('locale', 'ka'));

        return response()->json(['status' => 'verification_sent'], 202);
    }

    public function login(AuthLoginRequest $request): JsonResponse
    {
        return response()->json($this->auth->login($request->validated(), (string) $request->input('locale', 'ka')));
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->auth->userPayload($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout($request->user());

        return response()->json(['success' => true]);
    }
}
