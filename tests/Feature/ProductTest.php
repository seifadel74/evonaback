<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'Database\Seeders\CategorySeeder']);
        $this->seedProducts();
    }

    protected function seedProducts(): void
    {
        Product::factory()->count(5)->create(['is_active' => true, 'is_featured' => true]);
        Product::factory()->count(3)->create(['is_active' => true, 'is_featured' => false]);
        Product::factory()->create(['is_active' => false]);
    }

    public function test_can_list_products(): void
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data', 'meta']);
    }

    public function test_can_show_product(): void
    {
        $product = Product::where('is_active', true)->first();

        $response = $this->getJson("/api/v1/products/{$product->slug}");

        $response->assertStatus(200)
            ->assertJsonPath('data.slug', $product->slug);
    }

    public function test_returns_404_for_inactive_product(): void
    {
        $product = Product::where('is_active', false)->first();

        $response = $this->getJson("/api/v1/products/{$product->slug}");

        $response->assertStatus(404);
    }

    public function test_can_list_featured_products(): void
    {
        $response = $this->getJson('/api/v1/products/featured');

        $response->assertStatus(200);
    }

    public function test_can_search_products(): void
    {
        $product = Product::where('is_active', true)->first();

        $response = $this->getJson('/api/v1/products/search?q=' . urlencode(substr($product->name, 0, 5)));

        $response->assertStatus(200);
    }
}
