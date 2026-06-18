<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
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
        $this->product = Product::factory()->create(['stock' => 10, 'is_active' => true]);
    }

    protected function createCart(): Cart
    {
        $cart = Cart::create(['user_id' => $this->user->id]);
        $cart->items()->create(['product_id' => $this->product->id, 'quantity' => 1]);
        return $cart;
    }

    public function test_can_add_item_to_cart(): void
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/v1/cart/items', [
                'product_id' => $this->product->id,
                'quantity' => 2,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);
    }

    public function test_can_view_cart(): void
    {
        $this->createCart();

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/v1/cart');

        $response->assertStatus(200);
    }

    public function test_can_update_cart_item(): void
    {
        $cart = $this->createCart();
        $item = $cart->items()->first();

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->putJson("/api/v1/cart/items/{$item->id}", ['quantity' => 5]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 5]);
    }

    public function test_can_remove_cart_item(): void
    {
        $cart = $this->createCart();
        $item = $cart->items()->first();

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->deleteJson("/api/v1/cart/items/{$item->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('cart_items', ['id' => $item->id]);
    }

    public function test_cannot_add_more_than_stock(): void
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/v1/cart/items', [
                'product_id' => $this->product->id,
                'quantity' => 999,
            ]);

        $response->assertStatus(422);
    }
}
