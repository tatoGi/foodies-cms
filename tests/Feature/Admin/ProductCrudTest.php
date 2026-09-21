<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCrudTest extends TestCase
{
    use RefreshDatabase;

    // ─── index ────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_products_index(): void
    {
        $this->get(route('admin.products.index'))->assertRedirect();
    }

    public function test_admin_can_view_products_index(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.index'))
            ->assertOk();
    }

    // ─── create / store ───────────────────────────────────────────────────────

    public function test_admin_can_view_create_product(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.create'))
            ->assertOk();
    }

    public function test_admin_can_store_product(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.store'), [
                'sku' => 'PROD-001',
                'price' => 99.99,
                'is_active' => true,
                'names' => ['en' => 'Test Product'],
                'slugs' => ['en' => 'test-product'],
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['sku' => 'PROD-001']);
        $this->assertDatabaseHas('product_translations', ['locale' => 'en', 'slug' => 'test-product']);
    }

    public function test_store_product_requires_sku(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.store'), [
                'price' => 10.00,
                'names' => ['en' => 'No SKU Product'],
                'slugs' => ['en' => 'no-sku-product'],
            ])
            ->assertSessionHasErrors('sku');
    }

    public function test_store_product_requires_price(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.store'), [
                'sku' => 'PROD-002',
                'names' => ['en' => 'No Price Product'],
                'slugs' => ['en' => 'no-price-product'],
            ])
            ->assertSessionHasErrors('price');
    }

    public function test_store_product_rejects_duplicate_sku(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');

        Product::query()->create([
            'sku' => 'SAME-SKU',
            'price' => 10.00,
            'sort_order' => 1,
            'block_types' => [],
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.products.store'), [
                'sku' => 'SAME-SKU',
                'price' => 20.00,
                'names' => ['en' => 'Duplicate SKU'],
                'slugs' => ['en' => 'duplicate-sku'],
            ])
            ->assertSessionHasErrors('sku');
    }

    // ─── edit / update ────────────────────────────────────────────────────────

    public function test_admin_can_view_edit_product(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');

        $product = Product::query()->create([
            'sku' => 'EDIT-001',
            'price' => 50.00,
            'sort_order' => 1,
            'block_types' => [],
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.edit', $product))
            ->assertOk();
    }

    public function test_admin_can_update_product(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');

        $product = Product::query()->create([
            'sku' => 'UPDATE-001',
            'price' => 50.00,
            'sort_order' => 1,
            'block_types' => [],
            'is_active' => true,
        ]);
        ProductTranslation::query()->create([
            'product_id' => $product->id,
            'locale' => 'en',
            'title' => 'Old Name',
            'slug' => 'old-product-slug',
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.products.update', $product), [
                'sku' => 'UPDATE-001',
                'price' => 79.99,
                'is_active' => true,
                'names' => ['en' => 'Updated Product'],
                'slugs' => ['en' => 'updated-product-slug'],
            ])
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'price' => 79.99]);
        $this->assertDatabaseHas('product_translations', ['product_id' => $product->id, 'slug' => 'updated-product-slug']);
    }

    // ─── destroy ──────────────────────────────────────────────────────────────

    public function test_admin_can_delete_product(): void
    {
        $admin = $this->createAdminUser();

        $product = Product::query()->create([
            'sku' => 'DEL-001',
            'price' => 10.00,
            'sort_order' => 1,
            'block_types' => [],
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    // ─── reorder ──────────────────────────────────────────────────────────────

    public function test_admin_can_reorder_products(): void
    {
        $admin = $this->createAdminUser();

        $p1 = Product::query()->create(['sku' => 'SORT-1', 'price' => 10, 'sort_order' => 1, 'block_types' => [], 'is_active' => true]);
        $p2 = Product::query()->create(['sku' => 'SORT-2', 'price' => 10, 'sort_order' => 2, 'block_types' => [], 'is_active' => true]);

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.products.reorder'), [
                'ordered_ids' => [$p2->id, $p1->id],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        // reorderByIds uses 0-based index positions
        $this->assertEquals(0, $p2->fresh()->sort_order);
        $this->assertEquals(1, $p1->fresh()->sort_order);
    }

    public function test_reorder_returns_422_when_no_ids_provided(): void
    {
        $admin = $this->createAdminUser();

        $this->actingAs($admin, 'admin')
            ->postJson(route('admin.products.reorder'), [])
            ->assertStatus(422);
    }
}
