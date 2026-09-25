<?php

declare(strict_types=1);

namespace Tests\Feature\Website;

use App\Models\PosDevice;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductContentBlock;
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

    public function test_featured_menu_keeps_only_featured_dishes_and_their_categories(): void
    {
        $this->createLanguage('ka');
        $shawarma = $this->menuCategory('შაურმა', 'shawarma', 1);
        $drinks = $this->menuCategory('სასმელები', 'drinks', 2);
        $this->menuProduct($shawarma, 'ქათმის შაურმა', 'chicken', true);
        $this->menuProduct($shawarma, 'მინი შაურმა', 'mini', false);
        $this->menuProduct($drinks, 'წყალი', 'water', false);

        $response = $this->getJson('/api/web/menu?locale=ka&featured=1')->assertOk();

        $this->assertSame(['shawarma'], array_column($response->json('categories'), 'slug'));
        $this->assertSame(['chicken'], array_column($response->json('categories.0.products'), 'slug'));
        $this->assertCount(2, $this->getJson('/api/web/menu?locale=ka')->json('categories'));
    }

    private function menuCategory(string $name, string $slug, int $sort): ProductCategory
    {
        $category = ProductCategory::query()->create(['sort_order' => $sort, 'is_active' => true]);
        ProductCategoryTranslation::query()->create([
            'product_category_id' => $category->id,
            'locale' => 'ka',
            'name' => $name,
            'slug' => $slug,
        ]);

        return $category;
    }

    private function menuProduct(ProductCategory $category, string $title, string $slug, bool $featured): void
    {
        $product = Product::query()->create([
            'product_category_id' => $category->id,
            'sku' => $slug,
            'price' => 10,
            'is_active' => true,
            'is_featured' => $featured,
            'published' => true,
            'sort_order' => 1,
            'block_types' => [],
        ]);
        ProductTranslation::query()->create([
            'product_id' => $product->id,
            'locale' => 'ka',
            'title' => $title,
            'slug' => $slug,
        ]);
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

    public function test_product_detail_includes_seo_blocks_and_availability(): void
    {
        $this->createLanguage('ka');

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
            'cover_image' => 'pos-menu/42.jpg',
            'is_active' => true,
            'is_available' => false,
            'published' => true,
            'sort_order' => 1,
            'block_types' => [],
        ]);
        $translation = ProductTranslation::query()->create([
            'product_id' => $product->id,
            'locale' => 'ka',
            'title' => 'შაურმა დიდი',
            'slug' => 'shawarma-large',
            'excerpt' => 'ცხელი',
            'content' => 'გრძელი აღწერა',
            'meta_title' => 'შაურმა SEO',
            'meta_description' => 'აღწერა SEO',
        ]);
        ProductContentBlock::query()->create([
            'translation_id' => $translation->id,
            'type' => 'story',
            'data' => ['text' => 'სახლში მომზადებული'],
            'sort_order' => 1,
        ]);
        ProductIngredient::query()->create([
            'product_id' => $product->id,
            'external_id' => 7,
            'name' => ['ka' => 'ხახვი'],
            'is_removable' => true,
        ]);

        $this->getJson('/api/web/products/shawarma-large?locale=ka')
            ->assertOk()
            ->assertJsonPath('product.title', 'შაურმა დიდი')
            ->assertJsonPath('product.category', 'შაურმა')
            ->assertJsonPath('product.is_available', false)
            ->assertJsonPath('product.content', 'გრძელი აღწერა')
            ->assertJsonPath('product.ingredients.0.name', 'ხახვი')
            ->assertJsonPath('product.blocks.0.type', 'story')
            ->assertJsonPath('product.blocks.0.data.text', 'სახლში მომზადებული')
            ->assertJsonPath('seo.meta_title', 'შაურმა SEO')
            ->assertJsonPath('seo.meta_description', 'აღწერა SEO');
    }
}
