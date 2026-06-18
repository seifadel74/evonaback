<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group التصنيفات (Categories)
 *
 * @header Content-Type application/json
 * @header Accept application/json
 */
class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::with([
            'children' => fn($q) => $q->where('is_active', true)->orderBy('sort_order'),
        ])
            ->withCount('products')
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $childProductCounts = Category::whereIn('parent_id', $categories->pluck('id'))
            ->where('is_active', true)
            ->withCount('products')
            ->get()
            ->groupBy('parent_id')
            ->map(fn($group) => $group->sum('products_count'));

        $categories->each(function ($cat) use ($childProductCounts) {
            $cat->products_count += $childProductCounts->get($cat->id, 0);
        });

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $products = $category->products()
            ->withRelations()
            ->active()
            ->sortBy($request->sort)
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'products' => $products->items(),
                'meta' => [
                    'current_page' => $products->currentPage(),
                    'last_page' => $products->lastPage(),
                    'total' => $products->total(),
                ],
            ],
        ]);
    }
}
