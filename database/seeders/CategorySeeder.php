<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'الشموع' => [
                'icon' => '🕯️',
                'description' => 'شموع معطرة طبيعية فاخرة لتضفي أجواء ساحرة على منزلك',
                'children' => [
                    ['name' => 'شموع معطرة', 'description' => 'شموع بروائح مميزة تدوم طويلاً'],
                    ['name' => 'شموع طبيعية', 'description' => 'شموع من شمع الصويا الطبيعي ١٠٠٪'],
                    ['name' => 'شموع فاخرة', 'description' => 'شموع فاخرة بتصاميم راقية هدية مثالية'],
                ],
            ],
            'العناية بالبشرة' => [
                'icon' => '🧴',
                'description' => 'منتجات عناية بالبشرة طبيعية وعضوية لبشرة نضرة وصحية',
                'children' => [
                    ['name' => 'زيوت طبيعية', 'description' => 'زيوت طبيعية نقية للوجه والجسم'],
                    ['name' => 'كريمات ومرطبات', 'description' => 'كريمات مغذية ومرطبات عميقة للبشرة'],
                    ['name' => 'صابون طبيعي', 'description' => 'صابون طبيعي مصنوع يدوياً'],
                ],
            ],
            'العناية بالجسم' => [
                'icon' => '🫧',
                'description' => 'منتجات العناية اليومية بالجسم لنعومة وانتعاش يدوم',
                'children' => [
                    ['name' => 'لوشن للجسم', 'description' => 'لوشن مرطب للجسم بروائح منعشة'],
                    ['name' => 'سكراب ومقشر', 'description' => 'مقشرات طبيعية للجسم تمنحك بشرة ناعمة'],
                ],
            ],
            'هدايا' => [
                'icon' => '🎁',
                'description' => 'أطقمة هدايا فاخرة ومخصصة لكل المناسبات',
                'children' => [
                    ['name' => 'طقم هدايا', 'description' => 'أطقمة هدايا متكاملة بمناسبة مميزة'],
                    ['name' => 'هدايا مخصصة', 'description' => 'هدايا بتصميم حسب طلبك'],
                ],
            ],
        ];

        $sortOrder = 0;

        foreach ($categories as $parentName => $data) {
            $sortOrder += 10;
            $parentSlug = \Illuminate\Support\Str::slug($parentName);

            $parent = Category::firstOrCreate(
                ['slug' => $parentSlug],
                [
                    'name' => $parentName,
                    'description' => $data['description'],
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                ]
            );

            $childSort = 0;

            foreach ($data['children'] as $child) {
                $childSort += 10;
                $childSlug = \Illuminate\Support\Str::slug($child['name']);

                Category::firstOrCreate(
                    ['slug' => $childSlug],
                    [
                        'name' => $child['name'],
                        'description' => $child['description'],
                        'parent_id' => $parent->id,
                        'is_active' => true,
                        'sort_order' => $childSort,
                    ]
                );
            }
        }
    }
}
