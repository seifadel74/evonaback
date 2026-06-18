<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        $this->product = Product::factory()->create(['stock' => 10, 'price' => 100, 'is_active' => true]);

        $cart = Cart::create(['user_id' => $this->user->id]);
        $cart->items()->create(['product_id' => $this->product->id, 'quantity' => 2]);
    }

    public function test_checkout_creates_order_and_returns_payment_url(): void
    {
        $this->mock(StripeService::class, function (MockInterface $mock) {
            $session = new \stdClass;
            $session->url = 'https://checkout.stripe.com/test';
            $session->id = 'cs_test_123';
            $mock->shouldReceive('createCheckoutSession')->once()->andReturn($session);
        });

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/v1/checkout', [
                'shipping_address' => [
                    'address_line1' => '123 Main St',
                    'city' => 'Cairo',
                    'country' => 'MA',
                ],
                'notes' => 'Test order',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['success', 'data' => ['order', 'payment_url', 'session_id']]);

        $this->assertDatabaseHas('orders', ['user_id' => $this->user->id]);
    }

    public function test_checkout_fails_with_empty_cart(): void
    {
        $this->user->cart->items()->delete();

        $this->mock(StripeService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createCheckoutSession')->never();
        });

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/v1/checkout', [
                'shipping_address' => [
                    'address_line1' => '123 St',
                    'city' => 'Cairo',
                    'country' => 'MA',
                ],
            ]);

        $response->assertStatus(422);
    }

    public function test_checkout_with_coupon(): void
    {
        $coupon = Coupon::create([
            'code' => 'SAVE10',
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) {
            $session = new \stdClass;
            $session->url = 'https://checkout.stripe.com/test';
            $session->id = 'cs_test_456';
            $mock->shouldReceive('createCheckoutSession')->once()->andReturn($session);
        });

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/v1/checkout', [
                'shipping_address' => [
                    'address_line1' => '123 St',
                    'city' => 'Cairo',
                    'country' => 'MA',
                ],
                'coupon_code' => 'SAVE10',
            ]);

        $response->assertStatus(201);
    }

    public function test_checkout_preview_returns_pricing(): void
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/v1/checkout/preview');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['subtotal', 'shipping_cost', 'tax', 'discount', 'total']]);
    }

    public function test_webhook_handles_session_completed(): void
    {
        $order = $this->user->orders()->create([
            'order_number' => 'ORD-TEST',
            'status' => 'pending',
            'subtotal' => 200,
            'shipping_cost' => 0,
            'tax' => 30,
            'discount' => 0,
            'total' => 230,
            'payment_session_id' => 'cs_test_999',
            'shipping_address' => ['city' => 'Cairo'],
        ]);

        $this->mock(StripeService::class, function (MockInterface $mock) use ($order) {
            $mock->shouldReceive('handleWebhook')->once()->andReturn([
                'success' => true,
                'message' => 'Payment confirmed.',
            ]);
        });

        $response = $this->postJson('/api/v1/checkout/webhook/stripe', []);

        $response->assertStatus(200);
    }
}
