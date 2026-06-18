<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected string $adminToken;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'Database\Seeders\RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'Database\Seeders\AdminSeeder']);

        $this->admin = User::where('email', 'admin@evona.com')->first();
        $this->adminToken = $this->admin->createToken('test')->plainTextToken;

        $this->regularUser = User::factory()->create();
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $token = $this->regularUser->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/v1/admin/dashboard/stats');

        $response->assertStatus(403);
    }

    public function test_admin_can_view_dashboard_stats(): void
    {
        $response = $this->withHeader('Authorization', "Bearer $this->adminToken")
            ->getJson('/api/v1/admin/dashboard/stats');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_admin_can_list_products(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->withHeader('Authorization', "Bearer $this->adminToken")
            ->getJson('/api/v1/admin/products');

        $response->assertStatus(200);
    }

    public function test_admin_can_create_product(): void
    {
        $category = Category::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer $this->adminToken")
            ->postJson('/api/v1/admin/products', [
                    'name' => 'New Product',
                    'slug' => 'new-product',
                    'sku' => 'SKU-NEW-001',
                    'price' => 99.99,
                    'stock' => 10,
                    'category_id' => $category->id,
                    'description' => 'Description',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', ['name' => 'New Product']);
    }

    public function test_admin_can_update_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer $this->adminToken")
            ->putJson("/api/v1/admin/products/{$product->id}", ['name' => 'Updated']);

        $response->assertStatus(200);
        $this->assertEquals('Updated', $product->fresh()->name);
    }

    public function test_admin_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer $this->adminToken")
            ->deleteJson("/api/v1/admin/products/{$product->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted($product);
    }

    public function test_admin_can_manage_categories(): void
    {
        $createResponse = $this->withHeader('Authorization', "Bearer $this->adminToken")
            ->postJson('/api/v1/admin/categories', [
                'name' => 'Test Category',
                'slug' => 'test-category',
            ]);

        $createResponse->assertStatus(201);

        $category = Category::where('slug', 'test-category')->first();

        $updateResponse = $this->withHeader('Authorization', "Bearer $this->adminToken")
            ->putJson("/api/v1/admin/categories/{$category->id}", ['name' => 'Updated Category']);

        $updateResponse->assertStatus(200);
    }

    public function test_admin_can_list_customers(): void
    {
        $response = $this->withHeader('Authorization', "Bearer $this->adminToken")
            ->getJson('/api/v1/admin/customers');

        $response->assertStatus(200);
    }

    public function test_admin_can_update_inventory_batch(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $response = $this->withHeader('Authorization', "Bearer $this->adminToken")
            ->putJson('/api/v1/admin/inventory', [
                'items' => [
                    ['id' => $product->id, 'stock' => 50],
                ],
            ]);

        $response->assertStatus(200);
        $this->assertEquals(50, $product->fresh()->stock);
    }
}
