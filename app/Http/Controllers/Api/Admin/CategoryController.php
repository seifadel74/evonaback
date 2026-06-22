<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @group لوحة التحكم (Admin)
 *
 * @header Content-Type application/json
 * @header Accept application/json
 * @authenticated
 */
class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::with('children')->withCount('products')->orderBy('sort_order')->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'en_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:2048',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $category = Category::create($validated);

        return response()->json([
            'success' => true,
            'message' => __('messages.category_created'),
            'data' => $category,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $category = Category::with('children', 'parent')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'en_name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string|max:2048',
            'parent_id' => 'nullable|exists:categories,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => __('messages.category_updated'),
            'data' => $category->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        if ($category->products()->exists()) {
            return response()->json([
                'success' => false,
                'message' => __('messages.category_delete_has_products'),
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => __('messages.category_deleted'),
        ]);
    }
}
