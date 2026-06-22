<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'alt' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $path = $request->file('image')->store('products', 'public');

        $image = ProductImage::create([
            'product_id' => $validated['product_id'],
            'url' => Storage::url($path),
            'alt' => $validated['alt'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.image_uploaded'),
            'data' => $image,
        ], 201);
    }

    public function storeFromUrl(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'url' => 'required|url|max:2048',
            'alt' => 'nullable|string|max:255',
        ]);

        $maxSort = ProductImage::where('product_id', $validated['product_id'])->max('sort_order') ?? 0;

        $image = ProductImage::create([
            'product_id' => $validated['product_id'],
            'url' => $validated['url'],
            'alt' => $validated['alt'] ?? null,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.image_added_from_url'),
            'data' => $image,
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $image = ProductImage::findOrFail($id);

        $validated = $request->validate([
            'sort_order' => 'sometimes|integer|min:0',
            'is_primary' => 'sometimes|boolean',
            'alt' => 'nullable|string|max:255',
        ]);

        if (isset($validated['is_primary']) && $validated['is_primary']) {
            ProductImage::where('product_id', $image->product_id)
                ->where('id', '!=', $id)
                ->update(['is_primary' => false]);
        }

        $image->update($validated);

        return response()->json([
            'success' => true,
            'message' => __('messages.image_updated'),
            'data' => $image->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $image = ProductImage::findOrFail($id);
        $path = str_replace('/storage/', '', $image->url);
        Storage::disk('public')->delete($path);
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.image_deleted'),
        ]);
    }
}
