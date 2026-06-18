<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
    }

    public function test_can_create_address(): void
    {
        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->postJson('/api/v1/addresses', [
                'label' => 'Home',
                'full_name' => 'Test User',
                'phone' => '1234567890',
                'street' => '123 Main St',
                'city' => 'Cairo',
                'country' => 'Egypt',
                'is_default' => true,
            ]);

        $response->assertStatus(201);
    }

    public function test_can_list_addresses(): void
    {
        $this->user->addresses()->create([
            'label' => 'Home',
            'full_name' => 'Test',
            'phone' => '123',
            'street' => '123 St',
            'city' => 'Cairo',
            'country' => 'Egypt',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->getJson('/api/v1/addresses');

        $response->assertStatus(200);
    }

    public function test_can_update_address(): void
    {
        $address = $this->user->addresses()->create([
            'label' => 'Home',
            'full_name' => 'Test',
            'phone' => '123',
            'street' => '123 St',
            'city' => 'Cairo',
            'country' => 'Egypt',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->putJson("/api/v1/addresses/{$address->id}", [
                'label' => 'Work',
            ]);

        $response->assertStatus(200);
        $this->assertEquals('Work', $address->fresh()->label);
    }

    public function test_can_delete_address(): void
    {
        $address = $this->user->addresses()->create([
            'label' => 'Home',
            'full_name' => 'Test',
            'phone' => '123',
            'street' => '123 St',
            'city' => 'Cairo',
            'country' => 'Egypt',
        ]);

        $response = $this->withHeader('Authorization', "Bearer $this->token")
            ->deleteJson("/api/v1/addresses/{$address->id}");

        $response->assertStatus(200);
        $this->assertModelMissing($address);
    }
}
