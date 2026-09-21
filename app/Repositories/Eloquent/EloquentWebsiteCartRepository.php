<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\ShoppingCart;
use App\Repositories\Contracts\WebsiteCartRepositoryInterface;

class EloquentWebsiteCartRepository implements WebsiteCartRepositoryInterface
{
    public function findByToken(string $token): ?ShoppingCart
    {
        return ShoppingCart::query()
            ->with(['items.product.translations'])
            ->where('token', $token)
            ->first();
    }

    public function findByUserId(int $userId): ?ShoppingCart
    {
        return ShoppingCart::query()
            ->with(['items.product.translations'])
            ->where('user_id', $userId)
            ->first();
    }

    public function create(array $attributes): ShoppingCart
    {
        return ShoppingCart::query()->create($attributes);
    }

    public function delete(ShoppingCart $cart): void
    {
        $cart->delete();
    }
}
