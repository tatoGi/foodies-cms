<?php

declare(strict_types=1);

namespace App\Services\Website;

use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\ShoppingCart;
use App\Models\User;
use App\Repositories\Contracts\WebsiteCartRepositoryInterface;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WebsiteCartService
{
    public const CART_COOKIE = 'newhome_cart_token';

    public function __construct(
        private readonly WebsiteCartRepositoryInterface $cartRepository,
    ) {}

    /**
     * @return array{
     *   token:string,
     *   items:array<int, array<string, mixed>>,
     *   total:float,
     *   total_formatted:string,
     *   count:int,
     *   currency:string
     * }
     */
    public function getCart(?User $user, ?string $token): array
    {
        $resolvedToken = $this->normalizeToken($token);
        $cart = $this->resolveCart($user, $resolvedToken, false);

        return $this->cartPayload($cart, $resolvedToken);
    }

    /**
     * @return array{
     *   token:string,
     *   items:array<int, array<string, mixed>>,
     *   total:float,
     *   total_formatted:string,
     *   count:int,
     *   currency:string
     * }
     */
    public function addItem(?User $user, ?string $token, int $productId, int $quantity = 1): array
    {
        $product = Product::query()
            ->with('translations')
            ->where('id', $productId)
            ->where('is_active', true)
            ->first();

        if (! $product instanceof Product) {
            throw ValidationException::withMessages([
                'product_id' => 'Selected product is not available.',
            ]);
        }

        $resolvedToken = $this->normalizeToken($token, true);
        $cart = $this->resolveCart($user, $resolvedToken, true);
        $item = $cart->items()->firstOrNew(['product_id' => $product->id]);
        $item->quantity = max(1, (int) $item->quantity) + max(1, $quantity) - ($item->exists ? 0 : 1);
        $item->save();

        return $this->cartPayload($cart->fresh(['items.product.translations']), $resolvedToken);
    }

    /**
     * @return array{
     *   token:string,
     *   items:array<int, array<string, mixed>>,
     *   total:float,
     *   total_formatted:string,
     *   count:int,
     *   currency:string
     * }
     */
    public function updateItem(?User $user, ?string $token, int $productId, int $quantity): array
    {
        $resolvedToken = $this->normalizeToken($token);
        $cart = $this->resolveCart($user, $resolvedToken, false);

        if (! $cart) {
            return $this->cartPayload(null, $resolvedToken);
        }

        $item = $cart->items()->where('product_id', $productId)->first();
        if ($item) {
            $item->update([
                'quantity' => max(1, $quantity),
            ]);
        }

        return $this->cartPayload($cart->fresh(['items.product.translations']), $resolvedToken);
    }

    /**
     * @return array{
     *   token:string,
     *   items:array<int, array<string, mixed>>,
     *   total:float,
     *   total_formatted:string,
     *   count:int,
     *   currency:string
     * }
     */
    public function removeItem(?User $user, ?string $token, int $productId): array
    {
        $resolvedToken = $this->normalizeToken($token);
        $cart = $this->resolveCart($user, $resolvedToken, false);

        if (! $cart) {
            return $this->cartPayload(null, $resolvedToken);
        }

        $cart->items()->where('product_id', $productId)->delete();

        if (! $cart->fresh()->items()->exists() && ! $user) {
            $this->cartRepository->delete($cart);

            return $this->cartPayload(null, $resolvedToken);
        }

        return $this->cartPayload($cart->fresh(['items.product.translations']), $resolvedToken);
    }

    public function clearCart(?User $user, ?string $token): void
    {
        $resolvedToken = $this->normalizeToken($token, false);
        $cart = $this->resolveCart($user, $resolvedToken, false);

        if (! $cart) {
            return;
        }

        $cart->items()->delete();

        if (! $user) {
            $this->cartRepository->delete($cart);
        }
    }

    /**
     * @return array{
     *   items:array<int,array{product_id:int,name:string,quantity:int,unit_price:float,line_total:float,slug:?string,image:?string}>,
     *   basket:array<int,array{product_id:string,name:string,quantity:int,unit_price:float}>,
     *   total:float,
     *   total_formatted:string,
     *   currency:string
     * }
     */
    public function buildCheckoutCart(?User $user, ?string $token): array
    {
        $payload = $this->getCart($user, $token);

        return [
            'items' => collect($payload['items'])->map(static fn (array $item): array => [
                'product_id' => (int) $item['product_id'],
                'name' => (string) $item['name'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'line_total' => (float) $item['line_total'],
                'slug' => $item['slug'] ?? null,
                'image' => $item['image'] ?? null,
            ])->all(),
            'basket' => collect($payload['items'])->map(static fn (array $item): array => [
                'product_id' => (string) $item['product_id'],
                'name' => (string) $item['name'],
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
            ])->all(),
            'total' => (float) $payload['total'],
            'total_formatted' => (string) $payload['total_formatted'],
            'currency' => (string) $payload['currency'],
        ];
    }

    private function resolveCart(?User $user, ?string $token, bool $create): ?ShoppingCart
    {
        $userCart = $user ? $this->cartRepository->findByUserId($user->id) : null;
        $tokenCart = $token ? $this->cartRepository->findByToken($token) : null;

        if ($user && $tokenCart && (! $userCart || $userCart->id !== $tokenCart->id)) {
            if ($userCart) {
                foreach ($tokenCart->items as $tokenItem) {
                    $existingItem = $userCart->items()->firstOrNew([
                        'product_id' => $tokenItem->product_id,
                    ]);

                    $existingItem->quantity = max(1, (int) $existingItem->quantity) + (int) $tokenItem->quantity;
                    $existingItem->save();
                }

                $this->cartRepository->delete($tokenCart);
            } else {
                $tokenCart->forceFill([
                    'user_id' => $user->id,
                    'token' => $tokenCart->token ?: $token,
                ])->save();
                $userCart = $tokenCart->fresh(['items.product.translations']);
            }
        }

        $cart = $userCart ?: $tokenCart;

        if (! $cart && $create) {
            $cart = $this->cartRepository->create([
                'user_id' => $user?->id,
                'token' => $token ?: Str::uuid()->toString(),
                'currency' => 'GEL',
            ]);
        }

        if ($cart && blank($cart->token)) {
            $cart->forceFill([
                'token' => $token ?: Str::uuid()->toString(),
            ])->save();
        }

        return $cart?->fresh(['items.product.translations']);
    }

    /**
     * @return array{
     *   token:string,
     *   items:array<int, array<string, mixed>>,
     *   total:float,
     *   total_formatted:string,
     *   count:int,
     *   currency:string
     * }
     */
    private function cartPayload(?ShoppingCart $cart, string $token): array
    {
        $items = [];
        $total = 0.0;
        $count = 0;

        foreach ($cart?->items ?? [] as $item) {
            $product = $item->product;
            if (! $product) {
                continue;
            }

            $translation = $this->resolveTranslation($product, app()->getLocale());
            $unitPrice = (float) $product->price;
            $lineTotal = $unitPrice * (int) $item->quantity;
            $total += $lineTotal;
            $count += (int) $item->quantity;

            $items[] = [
                'product_id' => (int) $product->id,
                'slug' => $translation?->slug,
                'name' => $translation?->title ?: 'Product #'.$product->id,
                'image' => $product->cover_image,
                'category' => (string) ($product->category ?? ''),
                'quantity' => (int) $item->quantity,
                'unit_price' => $unitPrice,
                'line_total' => round($lineTotal, 2),
            ];
        }

        return [
            'token' => $token,
            'items' => $items,
            'total' => round($total, 2),
            'total_formatted' => number_format($total, 2, '.', ''),
            'count' => $count,
            'currency' => (string) ($cart?->currency ?? 'GEL'),
        ];
    }

    private function normalizeToken(?string $token, bool $generateIfMissing = true): string
    {
        $token = trim((string) $token);

        if ($token !== '') {
            return $token;
        }

        return $generateIfMissing ? Str::uuid()->toString() : '';
    }

    private function resolveTranslation(Product $product, string $locale): ?ProductTranslation
    {
        $normalizedLocale = strtolower($locale);
        $fallbackLocale = strtolower((string) config('app.fallback_locale', 'en'));

        return $product->translations->firstWhere('locale', $normalizedLocale)
            ?? $product->translations->firstWhere('locale', $fallbackLocale)
            ?? $product->translations->first();
    }
}
