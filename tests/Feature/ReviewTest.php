<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
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

    public function test_can_list_product_reviews(): void
    {
        $this->product->reviews()->create([
            'user_id' => $this->user->id,
            'rating' => 5,
            'comment' => 'Great product!',
        ]);

        $response = $this->getJson("/api/v1/products/{$this->product->slug}/reviews");

        $response->assertStatus(200);
    }

    public function test_can_create_review(): void
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/v1/reviews', [
                'product_id' => $this->product->id,
                'rating' => 5,
                'comment' => 'Amazing product! Highly recommend.',
            ]);

        $response->assertStatus(201);
    }

    public function test_requires_rating(): void
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/v1/reviews', [
                'product_id' => $this->product->id,
                'comment' => 'No rating',
            ]);

        $response->assertStatus(422);
    }
}
