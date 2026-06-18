<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group المنتجات (Products)
 *
 * @header Content-Type application/json
 * @header Accept application/json
 */
class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::withRelations()
            ->active()
            ->when($request->filled('category'), fn($q) => $q->byCategorySlug($request->category))
            ->when($request->filled('category_id'), fn($q) => $q->byCategory((int) $request->category_id))
            ->byBrand($request->brand)
            ->priceBetween($request->filled('min_price') ? (float) $request->min_price : null, $request->filled('max_price') ? (float) $request->max_price : null)
            ->sortBy($request->sort);

        $perPage = min($request->integer('per_page', 20), 50);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    public function filters(Request $request): JsonResponse
    {
        $query = Product::active()
            ->when($request->filled('category'), fn($q) => $q->byCategorySlug($request->category))
            ->when($request->filled('category_id'), fn($q) => $q->byCategory((int) $request->category_id));

        $brands = (clone $query)->whereNotNull('brand')->where('brand', '!=', '')->distinct()->pluck('brand')->sort()->values();

        $priceRange = (object) [
            'min' => (float) ((clone $query)->min('price') ?? 0),
            'max' => (float) ((clone $query)->max('price') ?? 0),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'brands' => $brands,
                'price_range' => $priceRange,
            ],
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $product = Product::withRelations()
            ->with(['reviews.user', 'attributes.values', 'activeVariants.attributeValues'])
            ->active()
            ->where('slug', $slug)
            ->firstOrFail();

        if ($request->user()) {
            $product->is_wishlisted = $product->wishlists()->where('user_id', $request->user()->id)->exists();
        }

        $product->rating_breakdown = collect(range(5, 1))->mapWithKeys(fn($s) => [$s => $product->reviews->where('rating', $s)->count()])->toArray();

        foreach ($product->activeVariants as $variant) {
            $variant->label = $variant->attributeValues->pluck('value')->implode(' / ');
        }

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    public function featured(): JsonResponse
    {
        $products = Product::withRelations()
            ->active()
            ->featured()
            ->limit(8)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate(['q' => 'required|string|min:2']);

        $query = Product::withRelations()
            ->active()
            ->search($request->q)
            ->sortBy($request->sort);

        $perPage = min($request->integer('per_page', 20), 50);
        $products = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }
}
