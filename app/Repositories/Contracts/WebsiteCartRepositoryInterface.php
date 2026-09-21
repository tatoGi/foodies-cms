<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\ShoppingCart;

interface WebsiteCartRepositoryInterface
{
    public function findByToken(string $token): ?ShoppingCart;

    public function findByUserId(int $userId): ?ShoppingCart;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): ShoppingCart;

    public function delete(ShoppingCart $cart): void;
}
