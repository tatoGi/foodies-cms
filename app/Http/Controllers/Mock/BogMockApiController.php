<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mock\CreateMockBogOrderRequest;
use App\Http\Requests\Mock\ShowMockPaymentDetailsRequest;
use Bog\Payment\Models\BogPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class BogMockApiController extends Controller
{
    public function token(): JsonResponse
    {
        return response()->json([
            'access_token' => 'mock-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ]);
    }

    public function createOrder(CreateMockBogOrderRequest $request): JsonResponse
    {
        $orderId = 'mock_'.Str::lower(Str::random(24));
        $redirectUrl = route('mock.bog.gateway', ['orderId' => $orderId]);
        $payload = $request->validated();

        BogPayment::query()->create([
            'bog_order_id' => $orderId,
            'external_order_id' => (string) ($payload['external_order_id'] ?? Str::uuid()->toString()),
            'user_id' => $payload['user_id'] ?? null,
            'amount' => (float) data_get($payload, 'purchase_units.total_amount', 0),
            'currency' => (string) data_get($payload, 'purchase_units.currency', 'GEL'),
            'status' => 'created',
            'redirect_url' => $redirectUrl,
            'request_payload' => $payload,
            'response_data' => [
                'id' => $orderId,
                'status' => 'created',
                'mock_status' => 'created',
                '_links' => [
                    'redirect' => ['href' => $redirectUrl],
                ],
            ],
            'save_card_requested' => (bool) ($payload['save_card'] ?? false),
        ]);

        return response()->json([
            'id' => $orderId,
            'status' => 'created',
            '_links' => [
                'redirect' => [
                    'href' => $redirectUrl,
                ],
            ],
        ]);
    }

    public function paymentDetails(ShowMockPaymentDetailsRequest $request, string $orderId): JsonResponse
    {
        $payment = BogPayment::query()->where('bog_order_id', $orderId)->first();

        if (! $payment) {
            return response()->json([
                'id' => $orderId,
                'status' => 'failed',
                'error' => 'Order not found',
            ], 404);
        }

        $status = (string) (
            data_get($payment->response_data, 'mock_status')
            ?? data_get($payment->callback_data, 'mock_status')
            ?? $payment->status
        );

        return response()->json([
            'id' => $payment->bog_order_id,
            'status' => $status,
            'purchase_units' => [
                'total_amount' => (float) $payment->amount,
                'currency' => $payment->currency,
            ],
        ]);
    }
}
