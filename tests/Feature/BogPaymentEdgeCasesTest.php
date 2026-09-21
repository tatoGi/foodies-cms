<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ShoppingCart;
use App\Models\User;
use Bog\Payment\Models\BogCard;
use Bog\Payment\Models\BogPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Edge-case coverage for the BOG payment integration.
 *
 * Core happy-path flows live in BogCheckoutFlowTest.
 * This file focuses on validation, guards, and error branches.
 */
class BogPaymentEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    // ─── startCheckout validation ─────────────────────────────────────────────

    public function test_checkout_start_requires_customer_name(): void
    {
        $this->postJson(route('checkout.bog.start'), [
            'customer_email' => 'test@example.com',
            'customer_phone' => '+995555000000',
            'delivery_address' => '123 Test St',
            'save_card' => false,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('customer_name');
    }

    public function test_checkout_start_requires_valid_email(): void
    {
        $this->postJson(route('checkout.bog.start'), [
            'customer_name' => 'Test User',
            'customer_email' => 'not-an-email',
            'customer_phone' => '+995555000000',
            'delivery_address' => '123 Test St',
            'save_card' => false,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('customer_email');
    }

    public function test_checkout_start_requires_phone(): void
    {
        $this->postJson(route('checkout.bog.start'), [
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
            'delivery_address' => '123 Test St',
            'save_card' => false,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('customer_phone');
    }

    public function test_checkout_start_requires_delivery_address(): void
    {
        $this->postJson(route('checkout.bog.start'), [
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+995555000000',
            'save_card' => false,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('delivery_address');
    }

    // ─── save card guard ──────────────────────────────────────────────────────

    public function test_save_card_without_auth_returns_validation_error(): void
    {
        // All fields valid but save_card=true with no authenticated user
        $this->postJson(route('checkout.bog.start'), [
            'customer_name' => 'Guest User',
            'customer_email' => 'guest@example.com',
            'customer_phone' => '+995555000000',
            'delivery_address' => '123 Street',
            'save_card' => true,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('save_card');
    }

    // ─── empty cart guard ─────────────────────────────────────────────────────

    public function test_checkout_start_fails_with_empty_cart(): void
    {
        // No cookie / no cart in DB
        $this->postJson(route('checkout.bog.start'), [
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
            'customer_phone' => '+995555000000',
            'delivery_address' => '123 Street',
            'save_card' => false,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('cart');
    }

    // ─── order & payment records ──────────────────────────────────────────────

    public function test_successful_checkout_creates_order_with_correct_amount(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct(price: 75.00);
        $this->attachCart($user, $product, quantity: 3);

        $this->actingAs($user, 'web')
            ->postJson(route('checkout.bog.start'), [
                'customer_name' => 'Demo User',
                'customer_email' => 'demo@example.com',
                'customer_phone' => '+995555000000',
                'delivery_address' => '123 Test St',
                'save_card' => false,
            ])
            ->assertOk();

        $order = Order::query()->latest('id')->firstOrFail();
        $this->assertEquals(225.00, (float) $order->total);
    }

    public function test_bog_payment_record_links_to_order(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->attachCart($user, $product);

        $this->actingAs($user, 'web')
            ->postJson(route('checkout.bog.start'), [
                'customer_name' => 'Demo User',
                'customer_email' => 'demo@example.com',
                'customer_phone' => '+995555000000',
                'delivery_address' => '123 Test St',
                'save_card' => false,
            ])
            ->assertOk();

        $payment = BogPayment::query()->latest('id')->firstOrFail();
        $this->assertNotNull($payment->external_order_id);
        $this->assertStringStartsWith('order:', $payment->external_order_id);
    }

    // ─── callback / status ────────────────────────────────────────────────────

    public function test_callback_with_unknown_order_id_returns_ok(): void
    {
        // BOG callback should not crash on an unknown order_id
        $this->postJson('/bog/callback', [
            'order_id' => 'mock_unknown_order_99999',
        ])->assertOk();
    }

    public function test_callback_updates_order_status_to_completed(): void
    {
        $user = User::factory()->create();
        $product = $this->createProduct();
        $this->attachCart($user, $product);

        $this->actingAs($user, 'web')
            ->postJson(route('checkout.bog.start'), [
                'customer_name' => 'Callback User',
                'customer_email' => 'callback@example.com',
                'customer_phone' => '+995555000000',
                'delivery_address' => '123 Test St',
                'save_card' => false,
            ])
            ->assertOk();

        $payment = BogPayment::query()->latest('id')->firstOrFail();

        // Mock mode requires response_data.mock_status = 'completed' to trigger completion
        $payment->update(['response_data' => array_merge(
            (array) ($payment->response_data ?? []),
            ['mock_status' => 'completed'],
        )]);

        $this->postJson('/bog/callback', ['order_id' => $payment->bog_order_id])
            ->assertOk();

        // Callback updates bog_payments.status only; order syncs on success-page visit
        $this->assertDatabaseHas('bog_payments', [
            'id' => $payment->id,
            'status' => 'completed',
        ]);
    }

    // ─── success / fail pages ─────────────────────────────────────────────────

    public function test_success_page_renders_for_completed_payment(): void
    {
        $payment = $this->createCompletedPayment();

        $this->get(route('checkout.bog.success', [
            'order_id' => $payment->bog_order_id,
            'external_order_id' => $payment->external_order_id,
        ]))->assertOk();
    }

    public function test_fail_page_renders_for_failed_payment(): void
    {
        $payment = $this->createCompletedPayment('failed');

        $this->get(route('checkout.bog.fail', [
            'order_id' => $payment->bog_order_id,
            'external_order_id' => $payment->external_order_id,
        ]))->assertOk();
    }

    public function test_success_page_redirects_when_payment_not_found(): void
    {
        $this->get(route('checkout.bog.success', [
            'order_id' => 'nonexistent',
            'external_order_id' => 'nonexistent',
        ]))->assertRedirect(route('checkout.index'));
    }

    // ─── saved card — API endpoint ────────────────────────────────────────────

    public function test_authenticated_user_can_delete_saved_card(): void
    {
        $rawToken = 'test-api-token-'.uniqid();
        $user = User::factory()->create([
            'api_token' => hash('sha256', $rawToken),
        ]);
        $card = $this->makeBogCard($user);

        $this->withToken($rawToken)
            ->deleteJson(route('api.website.checkout.cards.delete', $card))
            ->assertOk();

        $this->assertDatabaseMissing('bog_cards', ['id' => $card->id]);
    }

    public function test_user_cannot_delete_another_users_card(): void
    {
        $rawToken = 'test-api-token-other-'.uniqid();
        $owner = User::factory()->create();
        User::factory()->create(['api_token' => hash('sha256', $rawToken)]);

        $card = $this->makeBogCard($owner);

        // SavedCardService::delete silently skips mismatched ownership — card must survive
        $this->withToken($rawToken)
            ->deleteJson(route('api.website.checkout.cards.delete', $card))
            ->assertOk();

        $this->assertDatabaseHas('bog_cards', ['id' => $card->id]);
    }

    public function test_unauthenticated_user_cannot_delete_card(): void
    {
        $owner = User::factory()->create();
        $card = $this->makeBogCard($owner);

        $this->deleteJson(route('api.website.checkout.cards.delete', $card))
            ->assertStatus(401);
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

    private function createCompletedPayment(string $status = 'completed'): BogPayment
    {
        return BogPayment::query()->create([
            'bog_order_id' => 'mock_'.uniqid(),
            'external_order_id' => 'order:999:'.uniqid(),
            'amount' => 100,
            'currency' => 'GEL',
            'status' => $status,
            'request_payload' => [],
            'response_data' => ['mock_status' => $status],
            'save_card_requested' => false,
        ]);
    }

    private function makeBogCard(User $user): BogCard
    {
        return BogCard::query()->create([
            'user_id' => $user->id,
            'card_token' => 'tok_'.uniqid(),
            'card_mask' => '****1111',
            'card_type' => 'visa',
            'expiry_month' => '12',
            'expiry_year' => '2028',
            'is_default' => true,
        ]);
    }
}
