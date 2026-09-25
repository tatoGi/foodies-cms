<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductCategoryTranslation;
use App\Models\ProductTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_index_opens_on_the_first_category_and_can_switch_tabs(): void
    {
        $admin = $this->createAdminUser();
        $shawarma = $this->category('შაურმა', 1);
        $bakery = $this->category('საცხობი', 2);
        $this->product($shawarma, 'ქათმის შაურმა', 'pos-1');
        $this->product($bakery, 'ხაჭაპური', 'pos-2');
        $this->product(null, 'ძველი პროდუქტი', 'cms-1');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('შაურმა')
            ->assertSee('საცხობი')
            ->assertSee('უსათაურო')
            ->assertSee('ქათმის შაურმა')
            ->assertDontSee('ხაჭაპური');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index', ['category' => $bakery->id]))
            ->assertOk()
            ->assertSee('ხაჭაპური')
            ->assertDontSee('ქათმის შაურმა');

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index', ['category' => 'all']))
            ->assertOk()
            ->assertSee('ქათმის შაურმა')
            ->assertSee('ხაჭაპური')
            ->assertSee('ძველი პროდუქტი');
    }

    public function test_inactive_empty_categories_are_not_shown_as_tabs(): void
    {
        $admin = $this->createAdminUser();
        $this->category('ძველი კატეგორია', 1, false);
        $retired = $this->category('გაუქმებული კატეგორია', 2, false);
        $this->product($retired, 'დარჩენილი კერძი', 'pos-3');
        $this->category('შაურმა', 3);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('შაურმა')
            ->assertSee('გაუქმებული კატეგორია')
            ->assertDontSee('ძველი კატეგორია');
    }

    private function category(string $name, int $sort, bool $active = true): ProductCategory
    {
        $category = ProductCategory::query()->create([
            'external_source' => 'pos',
            'external_id' => $sort,
            'sort_order' => $sort,
            'is_active' => $active,
        ]);
        ProductCategoryTranslation::query()->create([
            'product_category_id' => $category->id,
            'locale' => 'ka',
            'name' => $name,
            'slug' => 'category-'.$sort,
        ]);

        return $category;
    }

    private function product(?ProductCategory $category, string $title, string $sku): void
    {
        $product = Product::query()->create([
            'external_source' => $category ? 'pos' : null,
            'external_id' => $category?->external_id,
            'product_category_id' => $category?->id,
            'sku' => $sku,
            'price' => 10,
            'sort_order' => 1,
            'block_types' => [],
            'is_active' => true,
        ]);
        ProductTranslation::query()->create([
            'product_id' => $product->id,
            'locale' => 'ka',
            'title' => $title,
            'slug' => $sku,
        ]);
    }
}
