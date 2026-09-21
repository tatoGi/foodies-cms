<?php

declare(strict_types=1);

namespace App\Http\Controllers\Checkout;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\ShowCheckoutResultRequest;
use App\Http\Requests\Checkout\StartBogCheckoutRequest;
use App\Services\Payments\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
    ) {}

    public function index(): View
    {
        $data = $this->checkoutService->checkoutData(
            auth()->user(),
            request()->cookie(\App\Services\Website\WebsiteCartService::CART_COOKIE),
        );

        return view('checkout.index', [
            'items' => $data['items'],
            'total' => $data['total'],
            'currency' => $data['currency'],
            'cards' => $data['cards'],
        ]);
    }

    public function start(StartBogCheckoutRequest $request): JsonResponse
    {
        $result = $this->checkoutService->startCheckout(
            $request->validated(),
            $request->user(),
            $request->cookie(\App\Services\Website\WebsiteCartService::CART_COOKIE),
        );

        return response()->json($result);
    }

    public function success(ShowCheckoutResultRequest $request): View|RedirectResponse
    {
        $payment = $this->checkoutService->resolvePaymentResult(
            $request->validated('order_id'),
            $request->validated('external_order_id'),
        );

        if (! $payment) {
            return redirect()->route('checkout.index')
                ->with('error', 'Payment not found.');
        }

        return view('checkout.success', ['payment' => $payment]);
    }

    public function fail(ShowCheckoutResultRequest $request): View|RedirectResponse
    {
        $payment = $this->checkoutService->resolvePaymentResult(
            $request->validated('order_id'),
            $request->validated('external_order_id'),
        );

        if (! $payment) {
            return redirect()->route('checkout.index')
                ->with('error', 'Payment not found.');
        }

        return view('checkout.fail', ['payment' => $payment]);
    }
}
