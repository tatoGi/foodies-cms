<?php

declare(strict_types=1);

namespace Tests\Feature\Pos;

use App\Models\PosDevice;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\Pos\PosDeviceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PosMenuSnapshotTest extends TestCase
{
    use RefreshDatabase;

    /** @var array{device: PosDevice, token: string, secret: string} */
    private array $credentials;

    protected function setUp(): void
    {
        parent::setUp();

        $this->credentials = app(PosDeviceService::class)->register('Test POS');
    }

    /** @param  array<string, mixed>  $payload */
    private function postSnapshot(array $payload, bool $signed = true): TestResponse
    {
        $body = json_encode($payload);
        $timestamp = (string) time();

        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'];
        if ($signed) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$this->credentials['token'];
            $server['HTTP_X_TIMESTAMP'] = $timestamp;
            $server['HTTP_X_SIGNATURE'] = hash_hmac('sha256', $timestamp.'.'.$body, $this->credentials['secret']);
        }

        return $this->call('POST', '/api/pos/v1/sync/menu/snapshot', [], [], [], $server, $body);
    }

    /** @return array<string, mixed> */
    private function snapshot(array $itemOverrides = [], ?array $items = null): array
    {
        return [
            'categories' => [
                [
                    'external_id' => 3,
                    'name' => ['ka' => 'შაურმა', 'en' => 'Shawarma'],
                    'description' => ['ka' => null, 'en' => null],
                    'sort_order' => 1,
                    'show_on_menu_board' => true,
                ],
            ],
            'items' => $items ?? [
                array_merge([
                    'external_id' => 42,
                    'shortcode' => 'CS',
                    'name' => ['ka' => 'ქათმის შაურმა', 'en' => 'Chicken Shawarma'],
                    'description' => ['ka' => 'გემრიელი', 'en' => 'Tasty'],
                    'price' => '12.00',
                    'is_available' => true,
                    'show_on_menu_board' => true,
                    'category_ids' => [3],
                    'ingredients' => [
                        ['external_id' => 7, 'name' => ['ka' => 'ხახვი', 'en' => 'Onion'], 'is_removable' => true],
                    ],
                    'addons' => [
                        ['external_id' => 5, 'name' => ['ka' => 'ყველი', 'en' => 'Cheese'], 'price' => '2.00'],
                    ],
                ], $itemOverrides),
            ],
        ];
    }

    public function test_snapshot_requires_device_authentication(): void
    {
        $this->postSnapshot($this->snapshot(), signed: false)->assertUnauthorized();
        $this->assertSame(0, Product::count());
    }

    public function test_snapshot_creates_categories_and_a_published_product_with_translations(): void
    {
        $this->postSnapshot($this->snapshot())->assertOk()->assertJsonPath('ok', true);

        $category = ProductCategory::sole();
        $this->assertSame(3, $category->external_id);
        $this->assertSame('Shawarma', $category->translations->firstWhere('locale', 'en')->name);
        $this->assertSame('შაურმა', $category->translations->firstWhere('locale', 'ka')->name);

        $product = Product::with(['translations', 'addons', 'ingredients'])->sole();
        $this->assertSame('pos', $product->external_source);
        $this->assertSame(42, $product->external_id);
        $this->assertSame('12.00', $product->price);
        $this->assertTrue($product->is_available);
        $this->assertTrue($product->is_active);
        $this->assertTrue($product->published, 'a new POS item is published so the public site mirrors the bakery menu');
        $this->assertSame($category->id, $product->product_category_id);
        $this->assertSame('Chicken Shawarma', $product->translations->firstWhere('locale', 'en')->title);
        $this->assertSame('ქათმის შაურმა', $product->translations->firstWhere('locale', 'ka')->title);
        $this->assertNotEmpty($product->translations->firstWhere('locale', 'ka')->slug);
        $this->assertNotSame(
            $product->translations->firstWhere('locale', 'ka')->slug,
            $product->translations->firstWhere('locale', 'en')->slug,
        );
        $this->assertSame('Onion', $product->ingredients->sole()->name['en']);
        $this->assertSame('2.00', $product->addons->sole()->price);
        $this->assertNotNull($this->credentials['device']->fresh()->last_sync_at);
    }

    public function test_snapshot_is_idempotent(): void
    {
        $this->postSnapshot($this->snapshot())->assertOk();
        $this->postSnapshot($this->snapshot())->assertOk();

        $this->assertSame(1, Product::count());
        $this->assertSame(1, ProductCategory::count());
        $this->assertSame(2, Product::first()->translations()->count());
        $this->assertSame(1, Product::first()->addons()->count());
    }

    public function test_resync_overwrites_pos_owned_fields_but_keeps_cms_owned_fields(): void
    {
        $this->postSnapshot($this->snapshot())->assertOk();

        $product = Product::sole();
        $product->update(['cover_image' => 'media/shawarma.jpg', 'published' => true, 'is_featured' => true]);
        $en = $product->translations()->where('locale', 'en')->first();
        $slug = $en->slug;
        $en->update(['meta_title' => 'Best shawarma in town', 'content' => 'Long marketing text']);

        $this->postSnapshot($this->snapshot([
            'price' => '13.50',
            'is_available' => false,
            'name' => ['ka' => 'ქათმის შაურმა დიდი', 'en' => 'Chicken Shawarma Large'],
        ]))->assertOk();

        $product = Product::sole();
        $this->assertSame('13.50', $product->price);
        $this->assertFalse($product->is_available);

        $en = $product->translations()->where('locale', 'en')->first();
        $this->assertSame('Chicken Shawarma Large', $en->title);

        $this->assertSame('media/shawarma.jpg', $product->cover_image);
        $this->assertTrue($product->published);
        $this->assertTrue($product->is_featured);
        $this->assertSame($slug, $en->slug, 'slug is CMS-owned; changing it would break URLs');
        $this->assertSame('Best shawarma in town', $en->meta_title);
        $this->assertSame('Long marketing text', $en->content);
    }

    public function test_items_missing_from_snapshot_are_deactivated_not_deleted(): void
    {
        $this->postSnapshot($this->snapshot())->assertOk();
        Product::sole()->update(['published' => true]);

        $this->postSnapshot($this->snapshot(items: []))->assertOk();

        $product = Product::sole();
        $this->assertFalse($product->is_active);
        $this->assertFalse($product->published);
    }

    public function test_removed_addons_and_ingredients_disappear_on_resync(): void
    {
        $this->postSnapshot($this->snapshot())->assertOk();
        $this->postSnapshot($this->snapshot(['ingredients' => [], 'addons' => []]))->assertOk();

        $product = Product::sole();
        $this->assertCount(0, $product->ingredients);
        $this->assertCount(0, $product->addons);
    }

    public function test_resync_does_not_republish_an_item_the_cms_hid(): void
    {
        $this->postSnapshot($this->snapshot())->assertOk();
        Product::sole()->update(['published' => false]);

        $this->postSnapshot($this->snapshot(['price' => '13.00']))->assertOk();

        $product = Product::sole();
        $this->assertFalse($product->published);
        $this->assertSame('13.00', $product->price);
    }

    public function test_snapshot_validates_required_item_fields(): void
    {
        $payload = $this->snapshot(['price' => 'abc']);
        unset($payload['items'][0]['external_id']);

        $this->postSnapshot($payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.external_id', 'items.0.price']);
    }
}
