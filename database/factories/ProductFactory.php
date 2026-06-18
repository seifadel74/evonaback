<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    private static array $productNames = [
        'شمعة الفانيليا الملكية',
        'شمعة اللافندر العضوي',
        'شمعة الياسمين الفاخرة',
        'شمعة العود والبخور',
        'شمعة خشب الصندل',
        'شمعة القرفة والبرتقال',
        'شمعة المسك الأبيض',
        'شمعة الورد الدمشقي',
        'زيت الأركان النقي',
        'زيت جوز الهند العضوي',
        'زيت اللوز الحلو',
        'زيت الجوجوبا الفاخر',
        'كريم الوجه المغذي',
        'كريم اليدين بالزبدة',
        'مرطب الشفاه الطبيعي',
        'صابون زيت الزيتون',
        'صابون اللافندر الطبيعي',
        'لوشن الجسم بالعسل',
        'سكراب الجسم بالملح',
        'طقم هدايا شموع فاخرة',
    ];

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(self::$productNames);

        return [
            'name' => $name,
            'slug' => fn(array $attrs) => Str::slug($attrs['name'] . '-' . Str::random(4)),
            'description' => fake()->paragraphs(3, true),
            'short_description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 20, 350),
            'compare_price' => fake()->optional(0.3)->randomFloat(2, 40, 400),
            'cost_price' => fake()->randomFloat(2, 10, 150),
            'sku' => fn(array $attrs) => 'EVN-' . strtoupper(Str::random(8)),
            'barcode' => fake()->optional()->ean13(),
            'stock' => fake()->numberBetween(0, 200),
            'stock_alert_threshold' => 5,
            'weight' => fake()->randomFloat(2, 0.1, 2),
            'is_featured' => fake()->boolean(20),
            'is_active' => true,
            'category_id' => Category::factory(),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn() => ['is_featured' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn() => ['is_active' => false]);
    }

    public function outOfStock(): static
    {
        return $this->state(fn() => ['stock' => 0]);
    }
}
