<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categories = Category::whereNotNull('parent_id')->get();
        $customer = User::where('email', 'user@evona.com')->first();

        $products = [
            [
                'name' => 'شمعة الفانيليا الملكية',
                'price' => 89.00,
                'compare_price' => 120.00,
                'stock' => 50,
                'is_featured' => true,
                'short_description' => 'شمعة فاخرة برائحة الفانيليا الدافئة',
                'description' => 'استمتع بأجواء دافئة ومريحة مع شمعتنا الملكية بالفانيليا. مصنوعة من شمع الصويا الطبيعي ١٠٠٪ مع فتيل قطني خالص.',
                'images' => 3,
            ],
            [
                'name' => 'شمعة اللافندر العضوي',
                'price' => 75.00,
                'stock' => 35,
                'is_featured' => true,
                'short_description' => 'رائحة اللافندر الهادئة للاسترخاء',
                'description' => 'مثالية للتأمل والاسترخاء بعد يوم طويل. تحتوي على زيت اللافندر العضوي النقي.',
                'images' => 3,
            ],
            [
                'name' => 'شمعة العود والبخور',
                'price' => 150.00,
                'compare_price' => 185.00,
                'stock' => 20,
                'is_featured' => true,
                'short_description' => 'رائحة شرقية فاخرة تدوم طويلاً',
                'description' => 'مزيج فاخر من عود العطور والبخور الهندي. تضفي جواً من الفخامة والرقي على منزلك.',
                'images' => 4,
            ],
            [
                'name' => 'زيت الأركان النقي',
                'price' => 120.00,
                'stock' => 45,
                'is_featured' => true,
                'short_description' => 'زيت أركان مغربي نقي للشعر والبشرة',
                'description' => 'زيت الأركان النقي المستخرج من حبوب الأركان المغربية. غني بفيتامين E والأحماض الدهنية الأساسية.',
                'images' => 2,
            ],
            [
                'name' => 'كريم الوجه المغذي',
                'price' => 95.00,
                'compare_price' => 130.00,
                'stock' => 30,
                'short_description' => 'كريم مغذي للوجه بزبدة الشيا',
                'description' => 'كريم غني بزبدة الشيا وزيت جوز الهند لترطيب عميق وتغذية البشرة طوال اليوم.',
                'images' => 2,
            ],
            [
                'name' => 'صابون اللافندر الطبيعي',
                'price' => 35.00,
                'stock' => 100,
                'short_description' => 'صابون طبيعي برائحة اللافندر المنعشة',
                'description' => 'صابون طبيعي مصنوع يدوياً من زيت الزيتون وزيت جوز الهند مع بتلات اللافندر المجففة.',
                'images' => 2,
            ],
            [
                'name' => 'لوشن الجسم بالعسل',
                'price' => 65.00,
                'stock' => 40,
                'short_description' => 'لوشن مرطب بالعسل الطبيعي',
                'description' => 'لوشن غني بالعسل الطبيعي وزبدة الشيا لترطيب عميق ونعومة تدوم طويلاً.',
                'images' => 2,
            ],
            [
                'name' => 'طقم هدايا شموع فاخرة',
                'price' => 250.00,
                'compare_price' => 320.00,
                'stock' => 15,
                'is_featured' => true,
                'short_description' => 'طقم ٣ شموع فاخرة بروائح مميزة',
                'description' => 'طقم هدايا فاخر يضم ٣ شموع معطرة بروائح: الفانيليا واللافندر والعود. في علبة هدايا أنيقة.',
                'images' => 4,
            ],
            [
                'name' => 'سكراب الجسم بالملح',
                'price' => 55.00,
                'stock' => 25,
                'short_description' => 'مقشر طبيعي بملح البحر والزيوت العطرية',
                'description' => 'مقشر طبيعي بملح البحر الميت وزيت اللوز الحلو واللوز المطحون. يمنحك بشرة ناعمة ومشرقة.',
                'images' => 2,
            ],
            [
                'name' => 'شمعة الورد الدمشقي',
                'price' => 110.00,
                'stock' => 25,
                'is_featured' => true,
                'short_description' => 'رائحة الورد الدمشقي الفاخرة',
                'description' => 'مستوحاة من ورود دمشق العريقة. شمعة فاخرة برائحة الورد الدمشقي مع لمسات من المسك والعنبر.',
                'images' => 3,
            ],
        ];

        foreach ($products as $index => $productData) {
            $category = $categories->random();
            $imageCount = $productData['images'] ?? 2;

            $product = Product::create([
                'name' => $productData['name'],
                'slug' => Str::slug($productData['name']),
                'description' => $productData['description'],
                'short_description' => $productData['short_description'],
                'price' => $productData['price'],
                'compare_price' => $productData['compare_price'] ?? null,
                'cost_price' => round($productData['price'] * 0.4, 2),
                'sku' => 'EVN-' . strtoupper(Str::random(8)),
                'stock' => $productData['stock'],
                'stock_alert_threshold' => 5,
                'weight' => fake()->randomFloat(2, 0.2, 1.5),
                'is_featured' => $productData['is_featured'] ?? false,
                'is_active' => true,
                'category_id' => $category->id,
            ]);

            for ($i = 0; $i < $imageCount; $i++) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => 'https://picsum.photos/640/640?random=' . ($product->id * 10 + $i),
                    'alt' => $productData['name'] . ($i > 0 ? ' - صورة ' . ($i + 1) : ''),
                    'sort_order' => $i,
                ]);
            }

            if ($customer && rand(0, 1)) {
                Review::create([
                    'rating' => rand(4, 5),
                    'comment' => 'منتج رائع جداً! أنصح به بشدة. الجودة ممتازة والتغليف جميل.',
                    'user_id' => $customer->id,
                    'product_id' => $product->id,
                ]);
            }

            if (rand(0, 1)) {
                Review::factory()->count(rand(1, 3))->create([
                    'product_id' => $product->id,
                ]);
            }
        }
    }
}
