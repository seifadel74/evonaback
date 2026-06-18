<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        Banner::create([
            'title_ar' => 'عطور وعناية فاخرة',
            'subtitle_ar' => 'اكتشف مجموعتنا المختارة بعناية من أفخم العطور ومنتجات العناية',
            'btn_text_ar' => 'تسوق الآن',
            'image' => '/storage/banners/hero-1.jpg',
            'link' => '/products',
            'sort_order' => 1,
        ]);

        Banner::create([
            'title_ar' => 'خصم يصل إلى 30%',
            'subtitle_ar' => 'على أجمل العطور الفاخرة لفترة محدودة',
            'btn_text_ar' => 'استفد من العرض',
            'image' => '/storage/banners/hero-2.jpg',
            'link' => '/categories/perfumes',
            'sort_order' => 2,
        ]);

        Banner::create([
            'title_ar' => 'منتجات العناية الطبيعية',
            'subtitle_ar' => 'منتجات عضوية 100% لبشرة صحية ونضرة',
            'btn_text_ar' => 'اكتشف',
            'image' => '/storage/banners/hero-3.jpg',
            'link' => '/categories/skincare',
            'sort_order' => 3,
        ]);
    }
}
