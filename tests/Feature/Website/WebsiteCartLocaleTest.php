<?php

declare(strict_types=1);

namespace Tests\Feature\Website;

use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteCartLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_cart_speaks_the_requested_language_and_returns_a_full_image_url(): void
    {
        $this->createLanguage('ka', true);
        $this->createLanguage('en', false);
        $product = Product::query()->create([
            'sku' => 'shawarma', 'price' => 12, 'is_active' => true, 'is_available' => true,
            'published' => true, 'sort_order' => 1, 'block_types' => [], 'cover_image' => 'pos-menu/8.jpg',
        ]);
        ProductTranslation::query()->create(['product_id' => $product->id, 'locale' => 'ka', 'title' => 'ქათმის შაურმა', 'slug' => 'qatmis-shaurma']);
        ProductTranslation::query()->create(['product_id' => $product->id, 'locale' => 'en', 'title' => 'Chicken Shawarma', 'slug' => 'chicken-shawarma']);

        $added = $this->postJson('/api/web/cart/items?locale=ka', ['product_id' => $product->id, 'quantity' => 2])->assertOk();
        $token = $added->json('cart.token');

        $this->withHeader('X-Cart-Token', $token)->getJson('/api/web/cart?locale=ka')
            ->assertOk()
            ->assertJsonPath('cart.items.0.name', 'ქათმის შაურმა')
            ->assertJsonPath('cart.items.0.slug', 'qatmis-shaurma')
            ->assertJsonPath('cart.items.0.quantity', 2);

        $this->assertStringContainsString('/storage/pos-menu/8.jpg', (string) $added->json('cart.items.0.image'));

        $this->withHeader('X-Cart-Token', $token)->getJson('/api/web/cart?locale=en')
            ->assertJsonPath('cart.items.0.name', 'Chicken Shawarma');
    }
}
