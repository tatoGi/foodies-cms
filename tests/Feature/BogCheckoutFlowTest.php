<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ShoppingCart;
use App\Models\User;
use Bog\Payment\Models\BogPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BogCheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_start_creates_order_and_returns_redirect_url(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->attachCart($user, $product, 2);

        $response = $this->actingAs($user, 'web')
            ->postJson(route('checkout.bog.start'), [
                'customer_name' => 'Demo User',
                'customer_email' => 'demo@example.com',
                'customer_phone' => '+995555000000',
                'delivery_address' => '123 Test Street',
                'save_card' => false,
            ]);

        $response->assertOk()
            ->assertJsonStructure(['redirect_url', 'bog_order_id', 'external_order_id']);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
        $this->assertDatabaseCount('bog_payments', 1);
    }

    public function test_callback_processes_completed_status(): void
    {
        $payment = $this->createPendingMockPayment();

        $response = $this->postJson('/bog/callback', [
            'order_id' => $payment->bog_order_id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('bog_payments', [
            'id' => $payment->id,
            'status' => 'completed',
        ]);
    }

    public function test_save_card_true_creates_saved_card_after_completed_callback(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->attachCart($user, $product, 1);

        $this->actingAs($user, 'web')
            ->postJson(route('checkout.bog.start'), [
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'customer_phone' => '+995555000001',
                'delivery_address' => '456 User Street',
                'save_card' => true,
            ])
            ->assertOk();

        $payment = BogPayment::query()->latest('id')->firstOrFail();
        $responseData = (array) ($payment->response_data ?? []);
        $responseData['mock_status'] = 'completed';
        $payment->update(['response_data' => $responseData]);

        $this->postJson('/bog/callback', [
            'order_id' => $payment->bog_order_id,
        ])->assertOk();

        $this->assertDatabaseCount('bog_cards', 1);
        $this->assertDatabaseHas('bog_cards', ['user_id' => $user->id]);
    }

    public function test_mock_mode_works_without_bog_keys(): void
    {
        config([
            'bog-payment.mock_mode' => true,
            'bog-payment.client_id' => null,
            'bog-payment.client_secret' => null,
        ]);

        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->attachCart($user, $product, 1);

        $response = $this->actingAs($user, 'web')
            ->postJson(route('checkout.bog.start'), [
                'customer_name' => 'Mock User',
                'customer_email' => 'mock@example.com',
                'customer_phone' => '+995555000002',
                'delivery_address' => '789 Mock Street',
                'save_card' => false,
            ]);

        $response->assertOk();
        $this->assertStringContainsString('/mock/bog/gateway/', (string) $response->json('redirect_url'));
    }

    // ─── helpers ──────────────────────────────────────────────────────────────

    private function createProduct(float $price = 50.00): Product
    {
        return Product::query()->create([
            'sku' => 'TEST-'.uniqid(),
            'cover_image' => null,
            'price' => $price,
            'category' => 'test',
            'stock' => 10,
            'is_active' => true,
            'block_types' => [],
            'sort_order' => 1,
            'is_featured' => false,
            'published' => true,
            'published_at' => now(),
            'is_ordered' => false,
            'is_rented' => false,
        ]);
    }

    private function attachCart(User $user, Product $product, int $quantity = 1): ShoppingCart
    {
        $cart = ShoppingCart::query()->create([
            'user_id' => $user->id,
            'currency' => 'GEL',
        ]);
        $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);

        return $cart;
    }

    private function createPendingMockPayment(): BogPayment
    {
        return BogPayment::query()->create([
            'bog_order_id' => 'mock_'.uniqid(),
            'external_order_id' => 'order:1:'.uniqid(),
            'amount' => 100,
            'currency' => 'GEL',
            'status' => 'created',
            'request_payload' => [
                'purchase_units' => [
                    'total_amount' => 100,
                    'currency' => 'GEL',
                    'basket' => [
                        [
                            'product_id' => '1',
                            'name' => 'Demo Product',
                            'quantity' => 1,
                            'unit_price' => 100,
                        ],
                    ],
                ],
            ],
            'response_data' => ['mock_status' => 'completed'],
            'save_card_requested' => false,
        ]);
    }
}
