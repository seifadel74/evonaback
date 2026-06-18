<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group المفضلة (Wishlist)
 *
 * @header Content-Type application/json
 * @header Accept application/json
 * @authenticated
 */
class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = $request->user()->wishlists()
            ->with('product.images')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    public function toggle(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $wishlist = Wishlist::where([
            'user_id' => $request->user()->id,
            'product_id' => $validated['product_id'],
        ])->first();

        if ($wishlist) {
            $wishlist->delete();
            $message = 'Removed from wishlist.';
        } else {
            Wishlist::create([
                'user_id' => $request->user()->id,
                'product_id' => $validated['product_id'],
            ]);
            $message = 'Added to wishlist.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }
}
