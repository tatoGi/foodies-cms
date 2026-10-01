<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Website\AddCartItemRequest;
use App\Http\Requests\Website\UpdateCartItemRequest;
use App\Models\Language;
use App\Models\User;
use App\Services\Website\WebsiteCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(
        private readonly WebsiteCartService $websiteCartService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->applyLocale($request);

        return response()->json([
            'cart' => $this->websiteCartService->getCart(
                $this->resolveFrontendUser($request),
                $request->header('X-Cart-Token') ?: $request->cookie(WebsiteCartService::CART_COOKIE),
            ),
        ]);
    }

    public function store(AddCartItemRequest $request): JsonResponse
    {
        $this->applyLocale($request);

        return response()->json([
            'cart' => $this->websiteCartService->addItem(
                $this->resolveFrontendUser($request),
                $request->header('X-Cart-Token') ?: $request->cookie(WebsiteCartService::CART_COOKIE),
                (int) $request->validated('product_id'),
                (int) ($request->validated('quantity') ?? 1),
            ),
        ]);
    }

    public function update(UpdateCartItemRequest $request, int $productId): JsonResponse
    {
        $this->applyLocale($request);

        return response()->json([
            'cart' => $this->websiteCartService->updateItem(
                $this->resolveFrontendUser($request),
                $request->header('X-Cart-Token') ?: $request->cookie(WebsiteCartService::CART_COOKIE),
                $productId,
                (int) $request->validated('quantity'),
            ),
        ]);
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        $this->applyLocale($request);

        return response()->json([
            'cart' => $this->websiteCartService->removeItem(
                $this->resolveFrontendUser($request),
                $request->header('X-Cart-Token') ?: $request->cookie(WebsiteCartService::CART_COOKIE),
                $productId,
            ),
        ]);
    }

    private function resolveFrontendUser(Request $request): ?User
    {
        return \App\Http\Middleware\AuthenticateFrontendUser::userFromToken($request);
    }

    /** Cart names and slugs follow `?locale=` (or the body's `locale`) when it is an active language. */
    private function applyLocale(Request $request): void
    {
        $code = strtolower(trim((string) ($request->query('locale') ?? $request->input('locale'))));

        if ($code !== '' && Language::query()->active()->where('code', $code)->exists()) {
            app()->setLocale($code);
        }
    }
}
