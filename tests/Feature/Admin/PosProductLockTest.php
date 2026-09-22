<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\ProductTranslation;
use App\Services\Pos\PosDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosProductLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_update_keeps_pos_owned_fields_and_saves_cms_fields(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');

        $product = Product::query()->create([
            'external_source' => 'pos',
            'external_id' => 42,
            'sku' => 'pos-42',
            'price' => 12,
            'category' => 'შაურმა',
            'is_active' => true,
            'is_available' => true,
            'sort_order' => 1,
            'block_types' => [],
            'published' => false,
        ]);
        ProductTranslation::query()->create([
            'product_id' => $product->id,
            'locale' => 'en',
            'title' => 'Shawarma',
            'slug' => 'shawarma',
        ]);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.products.update', $product), [
                'sku' => 'hacked',
                'price' => 99,
                'is_active' => false,
                'categories' => ['en' => 'სხვა'],
                'names' => ['en' => 'Hacked'],
                'slugs' => ['en' => 'shawarma-large'],
                'published' => true,
            ])
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();

        $this->assertSame('pos-42', $product->sku);
        $this->assertSame('12.00', (string) $product->price);
        $this->assertTrue($product->is_active);
        $this->assertTrue($product->is_available);
        $this->assertSame('შაურმა', $product->category);
        $this->assertTrue($product->published);
        $this->assertSame('Shawarma', $product->translations()->where('locale', 'en')->value('title'));
        $this->assertSame('shawarma-large', $product->translations()->where('locale', 'en')->value('slug'));
    }

    public function test_pos_product_form_marks_synced_fields(): void
    {
        $admin = $this->createAdminUser();
        $this->createLanguage('en');

        $product = Product::query()->create([
            'external_source' => 'pos',
            'external_id' => 7,
            'sku' => 'pos-7',
            'price' => 8,
            'sort_order' => 1,
            'block_types' => [],
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.products.edit', $product))
            ->assertOk()
            ->assertSee('სინქრონიზებულია POS-დან', false);
    }

    public function test_devices_page_shows_heartbeat(): void
    {
        $admin = $this->createAdminUser();
        $device = app(PosDeviceService::class)->register('სალარო')['device'];
        $device->update([
            'app_version' => 'test-pos',
            'last_seen_at' => now(),
            'status' => ['kitchen_enabled' => true, 'queue_depth' => 0],
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.pos-devices.index'))
            ->assertOk()
            ->assertSee('სალარო')
            ->assertSee('test-pos')
            ->assertSee('ონლაინ');
    }
}
