<?php

declare(strict_types=1);

namespace Tests\Feature\Website;

use App\Models\Product;
use App\Models\ProductTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerFavoritesTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $title, bool $published = true): Product
    {
        $product = Product::query()->create([
            'sku' => 'sku-'.$title, 'price' => 12, 'is_active' => true, 'is_available' => true,
            'published' => $published, 'sort_order' => 1, 'block_types' => [],
        ]);
        ProductTranslation::query()->create(['product_id' => $product->id, 'locale' => 'ka', 'title' => $title, 'slug' => 'slug-'.$product->id]);

        return $product;
    }

    public function test_favorites_can_be_added_listed_and_removed(): void
    {
        $this->createLanguage('ka');
        $user = User::factory()->create(['email_verified_at' => now()]);
        $token = $user->createToken('web', ['*'], now()->addDays(30))->plainTextToken;
        $shawarma = $this->product('ქათმის შაურმა');

        $this->withToken($token)->postJson('/api/web/me/favorites', ['product_id' => $shawarma->id])->assertOk();
        $this->withToken($token)->postJson('/api/web/me/favorites', ['product_id' => $shawarma->id])->assertOk();

        $this->withToken($token)->getJson('/api/web/me/favorites?locale=ka')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $shawarma->id)
            ->assertJsonPath('items.0.title', 'ქათმის შაურმა')
            ->assertJsonPath('items.0.price', '12.00');

        $this->withToken($token)->deleteJson("/api/web/me/favorites/{$shawarma->id}")->assertOk();
        $this->withToken($token)->getJson('/api/web/me/favorites')->assertJsonCount(0, 'items');
    }

    public function test_unpublished_products_cannot_be_favorited(): void
    {
        $this->createLanguage('ka');
        $user = User::factory()->create(['email_verified_at' => now()]);
        $draft = $this->product('draft', published: false);

        $this->withToken($user->createToken('web', ['*'], now()->addDays(30))->plainTextToken)
            ->postJson('/api/web/me/favorites', ['product_id' => $draft->id])
            ->assertStatus(422);
    }

    public function test_the_old_wishlist_routes_are_gone(): void
    {
        $this->getJson('/api/web/wishlist')->assertNotFound();
    }
}
