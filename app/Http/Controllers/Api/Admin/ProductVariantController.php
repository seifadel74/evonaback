<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductVariantController extends Controller
{
    public function attributes(Product $product): JsonResponse
    {
        $product->load('attributes.values');
        return response()->json(['success' => true, 'data' => $product->attributes]);
    }

    public function storeAttribute(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:select,color,image',
        ]);

        $attr = $product->attributes()->create($validated);
        return response()->json(['success' => true, 'data' => $attr], 201);
    }

    public function updateAttribute(Request $request, ProductAttribute $attribute): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:select,color,image',
            'sort_order' => 'sometimes|integer',
        ]);

        $attribute->update($validated);
        return response()->json(['success' => true, 'data' => $attribute]);
    }

    public function deleteAttribute(ProductAttribute $attribute): JsonResponse
    {
        $attribute->delete();
        return response()->json(['success' => true]);
    }

    public function storeAttributeValue(Request $request, ProductAttribute $attribute): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'required|string|max:255',
            'meta' => 'nullable|string|max:255',
        ]);

        $value = $attribute->values()->create($validated);
        return response()->json(['success' => true, 'data' => $value], 201);
    }

    public function updateAttributeValue(Request $request, ProductAttributeValue $attributeValue): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'sometimes|string|max:255',
            'meta' => 'nullable|string|max:255',
            'sort_order' => 'sometimes|integer',
        ]);

        $attributeValue->update($validated);
        return response()->json(['success' => true, 'data' => $attributeValue]);
    }

    public function deleteAttributeValue(ProductAttributeValue $attributeValue): JsonResponse
    {
        $attributeValue->delete();
        return response()->json(['success' => true]);
    }

    public function variants(Product $product): JsonResponse
    {
        $product->load(['variants.attributeValues']);
        return response()->json(['success' => true, 'data' => $product->variants]);
    }

    public function storeVariant(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'required|string|max:255|unique:product_variants,sku',
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'image' => 'nullable|string|max:2048',
            'is_active' => 'boolean',
            'attribute_value_ids' => 'nullable|array',
            'attribute_value_ids.*' => 'integer|exists:product_attribute_values,id',
        ]);

        $variant = $product->variants()->create([
            'sku' => $validated['sku'],
            'price' => $validated['price'] ?? null,
            'stock' => $validated['stock'] ?? 0,
            'image' => $validated['image'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (!empty($validated['attribute_value_ids'])) {
            $variant->attributeValues()->attach($validated['attribute_value_ids']);
        }

        $variant->load('attributeValues');
        return response()->json(['success' => true, 'data' => $variant], 201);
    }

    public function updateVariant(Request $request, ProductVariant $variant): JsonResponse
    {
        $validated = $request->validate([
            'sku' => 'sometimes|string|max:255|unique:product_variants,sku,' . $variant->id,
            'price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'image' => 'nullable|string|max:2048',
            'is_active' => 'boolean',
            'attribute_value_ids' => 'nullable|array',
            'attribute_value_ids.*' => 'integer|exists:product_attribute_values,id',
        ]);

        $variant->update($validated);

        if (array_key_exists('attribute_value_ids', $validated)) {
            $variant->attributeValues()->sync($validated['attribute_value_ids'] ?? []);
        }

        $variant->load('attributeValues');
        return response()->json(['success' => true, 'data' => $variant]);
    }

    public function deleteVariant(ProductVariant $variant): JsonResponse
    {
        $variant->delete();
        return response()->json(['success' => true]);
    }
}
