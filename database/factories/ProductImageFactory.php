<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductImageFactory extends Factory
{
    protected $model = ProductImage::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'url' => 'https://picsum.photos/seed/' . fake()->uuid() . '/640/640',
            'alt' => fake()->words(3, true),
        ];
    }

    public function primary(): static
    {
        return $this->state(fn() => ['sort_order' => 0]);
    }
}
