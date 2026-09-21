<?php

declare(strict_types=1);

namespace App\Services\Payments;

use Bog\Payment\Models\BogPayment;
use Bog\Payment\Services\BogPaymentService;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class MockBogPaymentService extends BogPaymentService
{
    public function createOrder(string $accessToken, array $payload, ?string $idempotencyKey = null, ?string $acceptLanguage = 'en')
    {
        $orderId = 'mock_'.Str::lower(Str::random(24));
        $totalAmount = (float) data_get($payload, 'purchase_units.total_amount', 0);
        $currency = (string) data_get($payload, 'purchase_units.currency', 'GEL');
        $redirectUrl = route('mock.bog.gateway', ['orderId' => $orderId]);

        BogPayment::query()->create([
            'bog_order_id' => $orderId,
            'external_order_id' => (string) ($payload['external_order_id'] ?? Str::uuid()->toString()),
            'user_id' => $payload['user_id'] ?? null,
            'amount' => $totalAmount,
            'currency' => $currency,
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

        return [
            'id' => $orderId,
            'status' => 'created',
            '_links' => [
                'redirect' => ['href' => $redirectUrl],
            ],
        ];
    }

    public function getOrderDetails(string $accessToken, string $orderId): ?array
    {
        $payment = BogPayment::query()->where('bog_order_id', $orderId)->first();
        if (! $payment) {
            return [
                'id' => $orderId,
                'status' => 'failed',
                'error' => 'Order not found',
            ];
        }

        $status = (string) (data_get($payment->response_data, 'mock_status')
            ?? data_get($payment->callback_data, 'mock_status')
            ?? $payment->status);

        return [
            'id' => $orderId,
            'status' => $status,
            'purchase_units' => [
                'total_amount' => (float) $payment->amount,
                'currency' => $payment->currency,
            ],
        ];
    }

    public function saveCard(string $accessToken, string $orderId, ?string $idempotencyKey = null): array
    {
        return [
            'success' => true,
            'data' => [
                'card_token' => 'mock_card_'.Str::lower(Str::random(28)),
                'card_mask' => '****'.random_int(1000, 9999),
                'card_type' => 'visa',
                'card_brand' => 'Visa',
                'card_holder_name' => 'MOCK USER',
                'expiry_month' => '12',
                'expiry_year' => '2030',
                'order_id' => $orderId,
            ],
            'message' => 'Card saved successfully',
        ];
    }

    public function chargeCard(string $accessToken, string $parentOrderId, array $paymentData): array
    {
        $orderId = 'mock_'.Str::lower(Str::random(24));
        $redirectUrl = route('mock.bog.gateway', ['orderId' => $orderId]);

        return [
            'success' => true,
            'data' => [
                'id' => $orderId,
                'order_id' => $orderId,
                'status' => 'created',
                'parent_order_id' => $parentOrderId,
                'amount' => (float) Arr::get($paymentData, 'amount', 0),
                'currency' => (string) Arr::get($paymentData, 'currency', 'GEL'),
                '_links' => [
                    'redirect' => ['href' => $redirectUrl],
                ],
            ],
            'message' => 'Saved card charged successfully',
        ];
    }
}
