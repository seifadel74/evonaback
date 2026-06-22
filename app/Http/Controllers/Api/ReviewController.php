<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group التقييمات (Reviews)
 *
 * @header Content-Type application/json
 * @header Accept application/json
 * @authenticated
 */
class ReviewController extends Controller
{
    public function index(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)->firstOrFail();

        $reviews = $product->reviews()
            ->with('user:id,name,avatar')
            ->where('is_approved', true)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $averageRating = $product->reviews()->where('is_approved', true)->avg('rating');

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => $reviews->items(),
                'average_rating' => round($averageRating, 1),
                'total_reviews' => $reviews->total(),
                'meta' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page' => $reviews->lastPage(),
                    'total' => $reviews->total(),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:10|max:1000',
        ]);

        $exists = Review::where('user_id', $request->user()->id)
            ->where('product_id', $validated['product_id'])
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => __('messages.review_exists'),
            ], 422);
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'product_id' => $validated['product_id'],
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
            'is_approved' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.review_created'),
            'data' => $review->load('user:id,name,avatar'),
        ], 201);
    }

    public function update(Request $request, Review $review): JsonResponse
    {
        if ($review->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => __('messages.unauthorized')], 403);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:10|max:1000',
        ]);

        $review->update($validated);

        return response()->json([
            'success' => true,
            'message' => __('messages.review_updated'),
            'data' => $review->load('user:id,name,avatar'),
        ]);
    }

    public function destroy(Request $request, Review $review): JsonResponse
    {
        if ($review->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => __('messages.unauthorized')], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.review_deleted'),
        ]);
    }
}
