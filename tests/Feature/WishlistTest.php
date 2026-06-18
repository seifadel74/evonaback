<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
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
        $this->product = Product::factory()->create(['is_active' => true]);
    }

    public function test_can_toggle_wishlist(): void
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/v1/wishlist', ['product_id' => $this->product->id]);

        $response->assertStatus(200);
    }

    public function test_can_list_wishlist(): void
    {
        $this->user->wishlists()->create(['product_id' => $this->product->id]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/v1/wishlist');

        $response->assertStatus(200);
    }
}
