<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group لوحة التحكم (Admin)
 *
 * @header Content-Type application/json
 * @header Accept application/json
 * @authenticated
 */
class InventoryController extends Controller
{
    public function updateBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:products,id',
            'items.*.stock' => 'required|integer|min:0',
        ]);

        $updated = [];
        foreach ($validated['items'] as $item) {
            $product = Product::find($item['id']);
            $product->update(['stock' => $item['stock']]);
            $updated[] = $product;
        }

        return response()->json([
            'success' => true,
            'message' => 'Inventory updated successfully.',
            'data' => $updated,
        ]);
    }
}
