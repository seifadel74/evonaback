<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->boolean(70) ? fake()->paragraph() : null,
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
        ];
    }

    public function highRating(): static
    {
        return $this->state(fn() => ['rating' => fake()->numberBetween(4, 5)]);
    }
}
