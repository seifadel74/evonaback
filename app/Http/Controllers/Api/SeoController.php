<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    public function robots(): Response
    {
        $content = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /cart\nDisallow: /checkout\nDisallow: /login\nDisallow: /register\nDisallow: /profile\nDisallow: /orders\nDisallow: /addresses\nDisallow: /returns\nDisallow: /wishlist\n\nSitemap: " . url('/sitemap.xml') . "\n";
        return response($content, 200, ['Content-Type' => 'text/plain']);
    }

    public function sitemap(): Response
    {
        $products = Product::active()->select('slug', 'updated_at')->get();
        $categories = Category::select('slug', 'updated_at')->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $xml .= "  <url><loc>" . url('/') . "</loc><priority>1.0</priority><changefreq>daily</changefreq></url>\n";
        $xml .= "  <url><loc>" . url('/about') . "</loc><priority>0.7</priority><changefreq>monthly</changefreq></url>\n";
        $xml .= "  <url><loc>" . url('/contact') . "</loc><priority>0.6</priority><changefreq>monthly</changefreq></url>\n";

        foreach ($categories as $cat) {
            $lastmod = $cat->updated_at ? $cat->updated_at->format('Y-m-d') : date('Y-m-d');
            $xml .= "  <url><loc>" . url('/?category=' . $cat->slug) . "</loc><priority>0.8</priority><lastmod>$lastmod</lastmod></url>\n";
        }

        foreach ($products as $p) {
            $lastmod = $p->updated_at ? $p->updated_at->format('Y-m-d') : date('Y-m-d');
            $xml .= "  <url><loc>" . url('/products/' . $p->slug) . "</loc><priority>0.9</priority><lastmod>$lastmod</lastmod></url>\n";
        }

        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
