<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Http\Requests\Checkout\ShowCheckoutResultRequest;
use App\Http\Requests\Checkout\StartBogCheckoutRequest;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\SavedCardService;
use Bog\Payment\Models\BogCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CheckoutService $checkoutService,
        private readonly SavedCardService $savedCardService,
    ) {}

    public function start(StartBogCheckoutRequest $request): JsonResponse
    {
        return response()->json(
            $this->checkoutService->startCheckout(
                $request->validated(),
                $request->user(),
            ),
        );
    }

    public function summary(Request $request): JsonResponse
    {
        $summary = $this->checkoutService->checkoutData($request->user());

        return response()->json(
            [
                ...$summary,
                'cards' => $this->checkoutService->serializeCards($summary['cards']),
            ],
        );
    }

    public function result(ShowCheckoutResultRequest $request): JsonResponse
    {
        $payment = $this->checkoutService->resolvePaymentResult(
            $request->validated('order_id'),
            $request->validated('external_order_id'),
        );

        if (! $payment || (int) $payment->user_id !== (int) $request->user()->id) {
            throw ValidationException::withMessages([
                'payment' => 'Payment record was not found for this account.',
            ]);
        }

        return response()->json([
            'payment' => [
                'bog_order_id' => (string) $payment->bog_order_id,
                'external_order_id' => (string) $payment->external_order_id,
                'status' => (string) $payment->status,
                'amount' => (float) $payment->amount,
                'currency' => (string) $payment->currency,
                'save_card_requested' => (bool) $payment->save_card_requested,
                'verified_at' => optional($payment->verified_at)->toISOString(),
            ],
        ]);
    }

    public function payWithSavedCard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'card_id' => ['required', 'integer', 'exists:bog_cards,id'],
        ]);

        return response()->json(
            $this->checkoutService->startSavedCardCheckout($validated, $request->user()),
        );
    }

    public function setDefaultCard(Request $request, BogCard $card): JsonResponse
    {
        $this->savedCardService->setDefault($request->user(), $card);

        return response()->json([
            'cards' => $this->checkoutService->serializeCards($this->savedCardService->list($request->user())),
        ]);
    }

    public function deleteCard(Request $request, BogCard $card): JsonResponse
    {
        $this->savedCardService->delete($request->user(), $card);

        return response()->json([
            'cards' => $this->checkoutService->serializeCards($this->savedCardService->list($request->user())),
        ]);
    }
}
