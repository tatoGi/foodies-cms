<?php

declare(strict_types=1);

namespace Tests\Feature\Website;

use App\Models\PosDevice;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductIngredient;
use App\Models\ProductTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebsiteMenuApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_returns_published_pos_items_in_the_requested_language(): void
    {
        $this->createLanguage('ka');
        $this->createLanguage('en', false);

        $category = ProductCategory::query()->create([
            'external_source' => 'pos',
            'external_id' => 3,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        ProductCategoryTranslation::query()->create([
            'product_category_id' => $category->id,
            'locale' => 'ka',
            'name' => 'შაურმა',
            'slug' => 'shawarma',
        ]);

        $product = Product::query()->create([
            'external_source' => 'pos',
            'external_id' => 42,
            'product_category_id' => $category->id,
            'sku' => 'pos-42',
            'price' => 12,
            'is_active' => true,
            'is_available' => false,
            'published' => true,
            'sort_order' => 1,
            'block_types' => [],
        ]);
        ProductTranslation::query()->create([
            'product_id' => $product->id,
            'locale' => 'ka',
            'title' => 'შაურმა დიდი',
            'slug' => 'shawarma-large',
        ]);
        ProductIngredient::query()->create([
            'product_id' => $product->id,
            'external_id' => 7,
            'name' => ['ka' => 'ხახვი', 'en' => 'Onion'],
            'is_removable' => true,
        ]);
        ProductAddon::query()->create([
            'product_id' => $product->id,
            'external_id' => 5,
            'name' => ['ka' => 'ყველი'],
            'price' => 2,
        ]);
        Product::query()->create([
            'sku' => 'draft',
            'price' => 5,
            'published' => false,
            'is_active' => true,
            'sort_order' => 2,
            'block_types' => [],
            'product_category_id' => $category->id,
        ]);

        $response = $this->getJson('/api/web/menu?locale=ka')
            ->assertOk()
            ->assertJsonPath('categories.0.slug', 'shawarma')
            ->assertJsonPath('categories.0.products.0.title', 'შაურმა დიდი')
            ->assertJsonPath('categories.0.products.0.price', '12.00')
            ->assertJsonPath('categories.0.products.0.is_available', false)
            ->assertJsonPath('categories.0.products.0.ingredients.0.name', 'ხახვი')
            ->assertJsonPath('categories.0.products.0.addons.0.price', '2.00');

        $this->assertCount(1, $response->json('categories.0.products'));

        $this->getJson('/api/web/menu/categories/shawarma')
            ->assertOk()
            ->assertJsonPath('products.0.slug', 'shawarma-large');
    }

    public function test_status_follows_the_pos_heartbeat(): void
    {
        $this->getJson('/api/web/status')
            ->assertOk()
            ->assertJsonPath('pos_online', false)
            ->assertJsonPath('accepting_online_orders', false);

        PosDevice::query()->create([
            'name' => 'სალარო',
            'token_hash' => hash('sha256', 'token'),
            'signing_secret' => 'secret',
            'is_active' => true,
            'last_seen_at' => now(),
        ]);

        $this->getJson('/api/web/status')->assertJsonPath('pos_online', true);
    }
}
