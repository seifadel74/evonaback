<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group السلة (Cart)
 *
 * @header Content-Type application/json
 * @header Accept application/json
 * @authenticated
 */
class CartController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $cart = Cart::with(['items.product.images', 'items.variant.attributeValues'])
            ->firstOrCreate(['user_id' => $request->user()->id]);

        $total = 0;
        foreach ($cart->items as $item) {
            $price = $item->variant?->price ?? $item->product->price;
            $total += $price * $item->quantity;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'cart' => $cart,
                'total' => $total,
                'items_count' => $cart->items->sum('quantity'),
            ],
        ]);
    }

    public function addItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'product_variant_id' => 'nullable|integer|exists:product_variants,id',
            'quantity' => 'required|integer|min:1|max:99',
        ]);

        $product = Product::active()->find($validated['product_id']);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product is unavailable.',
            ], 422);
        }

        $variant = null;
        if (!empty($validated['product_variant_id'])) {
            $variant = $product->variants()->where('is_active', true)->find($validated['product_variant_id']);
            if (!$variant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Variant is unavailable.',
                ], 422);
            }
        }

        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $cartItem = $cart->items()
            ->where('product_id', $product->id)
            ->where('product_variant_id', $variant?->id)
            ->first();
        $newQuantity = ($cartItem?->quantity ?? 0) + $validated['quantity'];

        $stock = $variant ? $variant->stock : $product->stock;
        if ($stock < $newQuantity) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock.',
            ], 422);
        }

        if ($cartItem) {
            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            $cart->items()->create([
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'quantity' => $validated['quantity'],
            ]);
        }

        $cart->load('items.product.images', 'items.variant.attributeValues');

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart.',
            'data' => $cart,
        ]);
    }

    public function updateItem(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1|max:99',
        ]);

        $cartItem = CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $request->user()->id))
            ->findOrFail($id);

        $product = $cartItem->product;
        $variant = $cartItem->variant;

        if (!$product || !$product->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Product is unavailable.',
            ], 422);
        }

        $stock = $variant ? $variant->stock : $product->stock;
        if ($stock < $validated['quantity']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient stock.',
            ], 422);
        }

        $cartItem->update(['quantity' => $validated['quantity']]);

        $cart = $cartItem->cart->load('items.product.images', 'items.variant.attributeValues');

        return response()->json([
            'success' => true,
            'data' => $cart,
        ]);
    }

    public function removeItem(Request $request, int $id): JsonResponse
    {
        $cartItem = CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $request->user()->id))
            ->findOrFail($id);

        $cartItem->delete();

        $cart = $cartItem->cart->load('items.product.images', 'items.variant.attributeValues');

        return response()->json([
            'success' => true,
            'data' => $cart,
        ]);
    }
}
