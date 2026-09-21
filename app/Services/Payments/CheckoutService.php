<?php

declare(strict_types=1);

namespace App\Services\Payments;

use App\Jobs\Notifications\SendWhatsAppTemplateNotification;
use App\Models\Order;
use App\Models\User;
use App\Services\Notifications\WhatsAppService;
use App\Services\Website\WebsiteCartService;
use Bog\Payment\Models\BogCard;
use Bog\Payment\Models\BogPayment;
use Bog\Payment\Services\BogAuthService;
use Bog\Payment\Services\BogPaymentService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly BogAuthService $bogAuthService,
        private readonly BogPaymentService $bogPaymentService,
        private readonly SavedCardService $savedCardService,
        private readonly WebsiteCartService $websiteCartService,
        private readonly WhatsAppService $whatsApp,
    ) {}

    /**
     * @return array{
     *   items:array<int,array{product_id:int,name:string,quantity:int,unit_price:float,line_total:float}>,
     *   basket:array<int,array{product_id:string,name:string,quantity:int,unit_price:float}>,
     *   total:float,
     *   total_formatted:string,
     *   currency:string,
     *   cards:Collection<int, BogCard>
     * }
     */
    public function checkoutData(?User $user, ?string $cartToken = null): array
    {
        $cartItems = $this->websiteCartService->buildCheckoutCart($user, $cartToken);
        $cards = $user ? $this->savedCardService->list($user) : collect();

        return [
            'items' => $cartItems['items'],
            'basket' => $cartItems['basket'],
            'total' => $cartItems['total'],
            'total_formatted' => number_format($cartItems['total'], 2, '.', ''),
            'currency' => 'GEL',
            'cards' => $cards,
        ];
    }

    /**
     * @param  array{customer_name:string,customer_email:string,customer_phone:string,delivery_address:string,save_card:bool}  $data
     * @return array{redirect_url:string,bog_order_id:string,external_order_id:string}
     */
    public function startCheckout(array $data, ?User $user, ?string $cartToken = null): array
    {
        if (($data['save_card'] ?? false) && ! $user) {
            throw ValidationException::withMessages([
                'save_card' => 'Authentication is required to save card.',
            ]);
        }

        $checkoutData = $this->checkoutData($user, $cartToken);
        if (($checkoutData['items'] ?? []) === []) {
            throw ValidationException::withMessages([
                'cart' => 'Cart is empty. Please add product(s) before checkout.',
            ]);
        }
        $localOrder = $this->createOrderDraft($data, $user, $checkoutData);
        $externalOrderId = $this->buildExternalOrderId($localOrder->id);
        $payload = $this->buildBogPayload(
            externalOrderId: $externalOrderId,
            basket: $checkoutData['basket'],
            total: $checkoutData['total'],
            currency: $checkoutData['currency'],
            saveCard: (bool) ($data['save_card'] ?? false),
            userId: $user?->id,
        );

        $token = $this->resolveAccessToken();
        $response = $this->bogPaymentService->createOrder(
            $token,
            $payload,
            Str::uuid()->toString(),
            app()->getLocale(),
        );

        $bogOrderId = (string) data_get($response, 'id', '');
        $redirectUrl = (string) data_get($response, '_links.redirect.href', '');

        if ($bogOrderId === '' || $redirectUrl === '') {
            throw ValidationException::withMessages([
                'payment' => 'Unable to initialize BOG payment.',
            ]);
        }

        $this->linkPaymentToLocalOrder($bogOrderId, $externalOrderId, $localOrder->id);

        return [
            'redirect_url' => $redirectUrl,
            'bog_order_id' => $bogOrderId,
            'external_order_id' => $externalOrderId,
        ];
    }

    /**
     * @param  array{card_id:int}  $data
     * @return array{redirect_url:string,bog_order_id:string,external_order_id:string}
     */
    public function startSavedCardCheckout(array $data, User $user): array
    {
        if (blank($user->phone) || blank($user->address)) {
            throw ValidationException::withMessages([
                'card_id' => 'Please update your phone and delivery address before using a saved card.',
            ]);
        }

        $checkoutData = $this->checkoutData($user);
        $card = BogCard::query()
            ->where('id', $data['card_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        if (blank($card->parent_order_id)) {
            throw ValidationException::withMessages([
                'card_id' => 'Selected card is not linked to a parent order.',
            ]);
        }

        $localOrder = $this->createOrderDraft([
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => (string) ($user->phone ?? ''),
            'delivery_address' => (string) ($user->address ?? ''),
            'save_card' => false,
        ], $user, $checkoutData);

        $externalOrderId = $this->buildExternalOrderId($localOrder->id);
        $token = $this->resolveAccessToken();
        $charge = $this->bogPaymentService->chargeCard($token, (string) $card->parent_order_id, [
            'amount' => $checkoutData['total'],
            'currency' => $checkoutData['currency'],
            'callback_url' => $this->callbackUrl(),
            'external_order_id' => $externalOrderId,
            'save_card' => false,
        ]);

        if (! ($charge['success'] ?? false)) {
            throw ValidationException::withMessages([
                'card_id' => (string) ($charge['message'] ?? 'Unable to charge saved card.'),
            ]);
        }

        $chargeData = (array) ($charge['data'] ?? []);
        $bogOrderId = (string) (data_get($chargeData, 'id') ?? data_get($chargeData, 'order_id'));
        $redirectUrl = (string) (
            data_get($chargeData, '_links.redirect.href')
            ?? data_get($chargeData, 'redirect_url')
            ?? $this->frontendRedirectUrl('/cart', [
                'checkout_status' => 'success',
                'order_id' => $bogOrderId,
            ])
        );

        if ($bogOrderId === '') {
            throw ValidationException::withMessages([
                'card_id' => 'Unable to obtain BOG order id for saved-card payment.',
            ]);
        }

        BogPayment::query()->updateOrCreate(
            ['bog_order_id' => $bogOrderId],
            [
                'external_order_id' => $externalOrderId,
                'user_id' => $user->id,
                'amount' => $checkoutData['total'],
                'currency' => $checkoutData['currency'],
                'status' => (string) data_get($chargeData, 'status', 'created'),
                'redirect_url' => $redirectUrl,
                'request_payload' => [
                    'callback_url' => $this->callbackUrl(),
                    'purchase_units' => [
                        'total_amount' => $checkoutData['total'],
                        'currency' => $checkoutData['currency'],
                        'basket' => $checkoutData['basket'],
                    ],
                    'external_order_id' => $externalOrderId,
                    'parent_order_id' => $card->parent_order_id,
                    'save_card' => false,
                ],
                'response_data' => $chargeData,
                'save_card_requested' => false,
            ],
        );

        $this->linkPaymentToLocalOrder($bogOrderId, $externalOrderId, $localOrder->id);

        return [
            'redirect_url' => $redirectUrl,
            'bog_order_id' => $bogOrderId,
            'external_order_id' => $externalOrderId,
        ];
    }

    public function resolvePaymentResult(?string $bogOrderId, ?string $externalOrderId): ?BogPayment
    {
        $query = BogPayment::query();
        $payment = null;

        if (filled($bogOrderId)) {
            $payment = (clone $query)->where('bog_order_id', $bogOrderId)->first();
        }

        if (! $payment && filled($externalOrderId)) {
            $payment = (clone $query)
                ->where('external_order_id', $externalOrderId)
                ->latest('id')
                ->first();
        }

        if (! $payment) {
            return null;
        }

        if (blank($payment->verified_at) || ! $this->isTerminalStatus((string) $payment->status)) {
            try {
                $this->bogPaymentService->handlePaymentCallback($payment->bog_order_id, [
                    'source' => 'checkout-redirect',
                ]);
                $payment->refresh();
            } catch (\Throwable) {
                // Keep existing DB state; page still renders latest persisted status.
            }
        }

        $this->syncLocalOrderStatus($payment);

        return $payment;
    }

    /**
     * @return array<int, array{id:int,card_mask:?string,card_brand:?string,card_type:?string,card_holder_name:?string,expiry_month:?string,expiry_year:?string,formatted_expiry:string,is_default:bool,parent_order_id:?string}>
     */
    public function serializeCards(Collection $cards): array
    {
        return $cards->map(static fn (BogCard $card): array => [
            'id' => (int) $card->id,
            'card_mask' => $card->card_mask,
            'card_brand' => $card->card_brand,
            'card_type' => $card->card_type,
            'card_holder_name' => $card->card_holder_name,
            'expiry_month' => $card->expiry_month,
            'expiry_year' => $card->expiry_year,
            'formatted_expiry' => (string) $card->formatted_expiry,
            'is_default' => (bool) $card->is_default,
            'parent_order_id' => $card->parent_order_id,
        ])->values()->all();
    }

    public function frontendRedirectUrl(string $path, array $query = []): string
    {
        $base = rtrim((string) config('bog-payment.frontend_url', 'http://localhost:3000'), '/');

        return $base.$path.(empty($query) ? '' : '?'.http_build_query($query));
    }

    /**
     * @param  array{
     *   customer_name:string,
     *   customer_email:string,
     *   customer_phone:string,
     *   delivery_address:string,
     *   save_card:bool
     * }  $checkoutInput
     * @param  array{
     *   items:array<int,array{product_id:int,name:string,quantity:int,unit_price:float,line_total:float}>,
     *   basket:array<int,array{product_id:string,name:string,quantity:int,unit_price:float}>,
     *   total:float,
     *   total_formatted:string,
     *   currency:string,
     *   cards:Collection<int, BogCard>
     * }  $checkoutData
     */
    private function createOrderDraft(array $checkoutInput, ?User $user, array $checkoutData): Order
    {
        return DB::transaction(function () use ($checkoutInput, $user, $checkoutData): Order {
            $customerName = trim((string) ($checkoutInput['customer_name'] ?? ''));
            $customerEmail = trim((string) ($checkoutInput['customer_email'] ?? ''));
            $customerPhone = trim((string) ($checkoutInput['customer_phone'] ?? ''));
            $deliveryAddress = trim((string) ($checkoutInput['delivery_address'] ?? ''));

            if ($user) {
                $customerName = $customerName !== '' ? $customerName : (string) $user->name;
                $customerEmail = $customerEmail !== '' ? $customerEmail : (string) $user->email;
                $customerPhone = $customerPhone !== '' ? $customerPhone : (string) ($user->phone ?? '');
                $deliveryAddress = $deliveryAddress !== '' ? $deliveryAddress : (string) ($user->address ?? '');
            }

            if ($customerName === '') {
                $customerName = 'Guest Customer';
            }

            if ($customerEmail === '') {
                $customerEmail = 'guest+'.Str::lower(Str::random(12)).'@example.com';
            }

            if ($customerPhone === '') {
                $customerPhone = 'N/A';
            }

            if ($deliveryAddress === '') {
                $deliveryAddress = 'N/A';
            }

            if ($user) {
                $user->forceFill([
                    'name' => $customerName,
                    'phone' => $customerPhone !== 'N/A' ? $customerPhone : $user->phone,
                    'address' => $deliveryAddress !== 'N/A' ? $deliveryAddress : $user->address,
                ])->save();
            }

            $order = Order::query()->create([
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'delivery_address' => $deliveryAddress,
                'total' => $checkoutData['total'],
                'status' => Order::STATUS_PENDING_PAYMENT,
                'ordered_at' => now(),
            ]);

            $order->items()->createMany(
                collect($checkoutData['items'])
                    ->map(static fn (array $item): array => [
                        'product_id' => $item['product_id'],
                        'product_name' => $item['name'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                    ])
                    ->all()
            );

            return $order;
        });
    }

    /**
     * @return array{
     *   callback_url:string,
     *   purchase_units:array{total_amount:float,currency:string,basket:array<int,array{product_id:string,name:string,quantity:int,unit_price:float}>},
     *   redirect_urls:array{success:string,fail:string},
     *   external_order_id:string,
     *   save_card:bool,
     *   capture:string,
     *   language:string,
     *   user_id?:int
     * }
     */
    private function buildBogPayload(
        string $externalOrderId,
        array $basket,
        float $total,
        string $currency,
        bool $saveCard,
        ?int $userId,
    ): array {
        $payload = [
            'callback_url' => $this->callbackUrl(),
            'purchase_units' => [
                'total_amount' => $total,
                'currency' => $currency,
                'basket' => $basket,
            ],
            'redirect_urls' => [
                'success' => $this->frontendRedirectUrl('/cart', [
                    'checkout_status' => 'success',
                    'external_order_id' => $externalOrderId,
                ]),
                'fail' => $this->frontendRedirectUrl('/cart', [
                    'checkout_status' => 'fail',
                    'external_order_id' => $externalOrderId,
                ]),
            ],
            'external_order_id' => $externalOrderId,
            'save_card' => $saveCard,
            'capture' => 'automatic',
            'language' => in_array(app()->getLocale(), ['ka', 'en', 'ru'], true) ? app()->getLocale() : 'en',
        ];

        if ($userId) {
            $payload['user_id'] = $userId;
        }

        return $payload;
    }

    private function callbackUrl(): string
    {
        $configured = trim((string) config('bog-payment.callback_url'));

        return $configured !== '' ? $configured : route('bog.callback');
    }

    private function resolveAccessToken(): string
    {
        $tokenResult = $this->bogAuthService->getAccessToken();
        $token = (string) data_get($tokenResult, 'access_token', '');

        if ($token === '') {
            throw ValidationException::withMessages([
                'payment' => 'Unable to authenticate with BOG.',
            ]);
        }

        return $token;
    }

    private function buildExternalOrderId(int $localOrderId): string
    {
        return "order:{$localOrderId}:".Str::lower(Str::random(10));
    }

    private function linkPaymentToLocalOrder(string $bogOrderId, string $externalOrderId, int $localOrderId): void
    {
        $payment = BogPayment::query()->where('bog_order_id', $bogOrderId)->first();
        if (! $payment) {
            return;
        }

        $requestPayload = (array) ($payment->request_payload ?? []);
        $requestPayload['local_order_id'] = $localOrderId;
        $requestPayload['external_order_id'] = $externalOrderId;

        $payment->update([
            'external_order_id' => $externalOrderId,
            'request_payload' => $requestPayload,
        ]);
    }

    private function syncLocalOrderStatus(BogPayment $payment): void
    {
        $localOrderId = (int) (
            data_get($payment->request_payload, 'local_order_id')
            ?? $this->extractLocalOrderIdFromExternal((string) $payment->external_order_id)
        );

        if ($localOrderId <= 0) {
            return;
        }

        $order = Order::query()->find($localOrderId);
        if (! $order) {
            return;
        }

        $status = strtolower((string) $payment->status);
        $mappedStatus = Order::STATUS_PENDING_PAYMENT;

        if (in_array($status, ['completed', 'approved', 'succeeded'], true)) {
            $mappedStatus = Order::STATUS_PAID;
        } elseif (in_array($status, ['failed', 'rejected', 'cancelled', 'error'], true)) {
            $mappedStatus = Order::STATUS_CANCELLED;
        } elseif (in_array($status, ['created', 'pending'], true)) {
            $mappedStatus = Order::STATUS_PENDING_PAYMENT;
        }

        $previousStatus = (string) $order->status;
        if ($previousStatus !== $mappedStatus) {
            $order->update(['status' => $mappedStatus]);
        }

        if ($mappedStatus === Order::STATUS_PAID && $previousStatus !== Order::STATUS_PAID) {
            $this->dispatchNewOrderNotification($order->fresh('items') ?? $order);

            if ($payment->user_id) {
                $this->websiteCartService->clearCart(
                    User::query()->find((int) $payment->user_id),
                    null,
                );
            }
        }
    }

    private function dispatchNewOrderNotification(Order $order): void
    {
        $items = $order->items()->get()->map(
            static fn ($item): string => sprintf(
                '• %s × %d (%.2f ₾)',
                (string) $item->product_name,
                (int) $item->quantity,
                (float) $item->unit_price * (int) $item->quantity,
            ),
        )->implode("\n");

        SendWhatsAppTemplateNotification::dispatch(
            $this->whatsApp->templateName('new_order'),
            [
                (string) $order->id,
                (string) $order->customer_name,
                (string) $order->customer_phone,
                (string) $order->customer_email,
                (string) $order->delivery_address,
                number_format((float) $order->total, 2, '.', ''),
                mb_substr($items === '' ? '—' : $items, 0, 900),
            ],
        );
    }

    private function extractLocalOrderIdFromExternal(string $externalOrderId): int
    {
        if (! str_starts_with($externalOrderId, 'order:')) {
            return 0;
        }

        $parts = explode(':', $externalOrderId);

        return isset($parts[1]) ? (int) $parts[1] : 0;
    }

    private function isTerminalStatus(string $status): bool
    {
        return in_array(strtolower($status), [
            'completed',
            'approved',
            'succeeded',
            'failed',
            'rejected',
            'cancelled',
            'error',
        ], true);
    }
}
