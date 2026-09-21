<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mock;

use App\Http\Controllers\Controller;
use App\Http\Requests\Mock\CompleteMockGatewayRequest;
use Bog\Payment\Models\BogPayment;
use Bog\Payment\Services\BogPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BogMockGatewayController extends Controller
{
    public function __construct(
        private readonly BogPaymentService $bogPaymentService,
    ) {}

    public function show(string $orderId): View|RedirectResponse
    {
        $payment = BogPayment::query()->where('bog_order_id', $orderId)->first();

        if (! $payment) {
            return redirect()->route('checkout.index')
                ->with('error', 'Mock order not found.');
        }

        return view('mock.bog-gateway', ['payment' => $payment]);
    }

    public function complete(CompleteMockGatewayRequest $request, string $orderId): RedirectResponse
    {
        $payment = BogPayment::query()->where('bog_order_id', $orderId)->firstOrFail();
        $status = $request->validated('result') === 'success' ? 'completed' : 'failed';
        $responseData = (array) ($payment->response_data ?? []);
        $redirectUrls = (array) data_get($payment->request_payload, 'redirect_urls', []);
        $responseData['mock_status'] = $status;

        $payment->update([
            'response_data' => $responseData,
            'status' => $status === 'completed' ? 'created' : 'failed',
        ]);

        $this->bogPaymentService->handlePaymentCallback($payment->bog_order_id, [
            'order_id' => $payment->bog_order_id,
            'mock_status' => $status,
            'source' => 'mock-gateway',
        ]);

        $targetUrl = (string) (
            $status === 'completed'
                ? ($redirectUrls['success'] ?? '')
                : ($redirectUrls['fail'] ?? '')
        );

        if ($targetUrl !== '') {
            return redirect()->away($targetUrl);
        }

        return redirect()->route(
            $status === 'completed' ? 'checkout.bog.success' : 'checkout.bog.fail',
            ['order_id' => $payment->bog_order_id],
        );
    }
}
