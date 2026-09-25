<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductIngredient;
use App\Models\ProductTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductMenuFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_forms_hide_furniture_fields_and_show_menu_fields(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('ka');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('name="product_category_id"', false)
            ->assertSee('name="excerpts[ka]"', false)
            ->assertDontSee('name="brand"', false)
            ->assertDontSee('spec_dimensions', false)
            ->assertDontSee('40*60', false)
            ->assertDontSee('show_in_reels', false);

        $product = Product::query()->create([
            'external_source' => 'pos',
            'external_id' => 3,
            'sku' => 'pos-3',
            'price' => 11,
            'sort_order' => 1,
            'block_types' => [],
            'is_active' => true,
            'is_available' => false,
            'brand' => 'NewHome',
        ]);
        ProductTranslation::query()->create([
            'product_id' => $product->id,
            'locale' => 'ka',
            'title' => 'ქათმის შაურმა',
            'slug' => 'chicken-shawarma',
            'excerpt' => 'პიტა და ქათამი',
        ]);
        ProductIngredient::query()->create([
            'product_id' => $product->id,
            'external_id' => 1,
            'name' => ['ka' => 'ხახვი'],
            'is_removable' => true,
        ]);
        ProductAddon::query()->create([
            'product_id' => $product->id,
            'external_id' => 2,
            'name' => ['ka' => 'ყველი'],
            'price' => 2,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('ხახვი', false)
            ->assertSee('ყველი', false)
            ->assertSee('სურათი სალაროდან მოდის', false)
            ->assertDontSee('name="cover_image"', false)
            ->assertSee('ამოიწურა', false)
            ->assertSee('პიტა და ქათამი', false)
            ->assertDontSee('name="brand"', false);
    }

    public function test_cms_product_saves_category_and_excerpt_without_clearing_old_brand(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('ka');

        $category = ProductCategory::query()->create([
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
            'sku' => 'cms-1',
            'price' => 9,
            'sort_order' => 1,
            'block_types' => [],
            'is_active' => true,
            'brand' => 'OldBrand',
            'stock' => 4,
        ]);
        ProductTranslation::query()->create([
            'product_id' => $product->id,
            'locale' => 'ka',
            'title' => 'ლიმონათი',
            'slug' => 'lemonade',
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.products.update', $product), [
                'sku' => 'cms-1',
                'price' => 10,
                'product_category_id' => $category->id,
                'names' => ['ka' => 'ლიმონათი'],
                'slugs' => ['ka' => 'lemonade'],
                'excerpts' => ['ka' => 'ცივი'],
            ])
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertSame($category->id, $product->product_category_id);
        $this->assertSame('OldBrand', $product->brand);
        $this->assertSame(4, $product->stock);
        $this->assertSame('ცივი', $product->translations()->where('locale', 'ka')->value('excerpt'));
    }

    public function test_saving_a_product_refreshes_the_site_cache(): void
    {
        config([
            'services.frontend.revalidate_url' => 'http://frontend.test/api/revalidate',
            'services.frontend.revalidate_secret' => 'test-secret',
        ]);
        Http::fake(['http://frontend.test/*' => Http::response(['ok' => true])]);
        $admin = $this->createAdminUser();
        $this->createLanguage('ka');
        $product = Product::query()->create([
            'sku' => 'cms-2',
            'price' => 9,
            'sort_order' => 1,
            'block_types' => [],
            'is_active' => true,
        ]);
        ProductTranslation::query()->create([
            'product_id' => $product->id,
            'locale' => 'ka',
            'title' => 'ლიმონათი',
            'slug' => 'lemonade',
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.products.update', $product), [
                'sku' => 'cms-2',
                'price' => 9,
                'is_featured' => 1,
                'names' => ['ka' => 'ლიმონათი'],
                'slugs' => ['ka' => 'lemonade'],
            ])
            ->assertRedirect(route('admin.products.index'));

        $this->assertTrue($product->refresh()->is_featured);
        Http::assertSent(function (Request $request): bool {
            $tags = $request->data()['tags'] ?? [];

            return $request->url() === 'http://frontend.test/api/revalidate'
                && in_array('pages', $tags, true)
                && in_array('menu', $tags, true)
                && in_array('product:lemonade', $tags, true);
        });
    }
}
