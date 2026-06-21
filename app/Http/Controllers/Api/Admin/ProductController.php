<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;

/**
 * @group لوحة التحكم (Admin)
 *
 * @header Content-Type application/json
 * @header Accept application/json
 * @authenticated
 */
class ProductController extends Controller
{
    public function exportCsv(): \Illuminate\Http\Response
    {
        $products = Product::with('category')->get();
        $csv = "name,sku,price,compare_price,stock,category,is_active\n";
        foreach ($products as $p) {
            $csv .= sprintf(
                '"%s","%s",%s,%s,%d,"%s",%d' . "\n",
                str_replace('"', '""', $p->name),
                $p->sku,
                $p->price,
                $p->compare_price ?? '',
                $p->stock,
                $p->category?->name ?? '',
                $p->is_active ? 1 : 0
            );
        }
        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="products.csv"',
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['category', 'images'])->withCount('reviews');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $products = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total(),
                'per_page' => $products->perPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'en_name' => 'nullable|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'sku' => 'required|string|max:100|unique:products,sku',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'unit' => 'nullable|string|max:50',
            'weight' => 'nullable|numeric|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(5);
        $validated['is_featured'] = $validated['is_featured'] ?? false;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data' => $product->load(['category', 'images']),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $product = Product::with(['category', 'images', 'reviews'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'en_name' => 'nullable|string|max:255',
            'description' => 'sometimes|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'sometimes|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'sku' => 'sometimes|string|max:100|unique:products,sku,' . $id,
            'stock' => 'sometimes|integer|min:0',
            'category_id' => 'sometimes|exists:categories,id',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'unit' => 'nullable|string|max:50',
            'weight' => 'nullable|numeric|min:0',
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data' => $product->fresh()->load(['category', 'images']),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ]);
    }

    public function trashed(): JsonResponse
    {
        $products = Product::onlyTrashed()->with(['category', 'images'])->orderBy('deleted_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function restore(int $id): JsonResponse
    {
        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return response()->json([
            'success' => true,
            'message' => 'Product restored successfully.',
            'data' => $product->load(['category', 'images']),
        ]);
    }

    public function duplicate(int $id): JsonResponse
    {
        $original = Product::findOrFail($id);
        $product = $original->replicate();
        $product->name = $original->name . ' (نسخة)';
        $product->slug = Str::slug($original->name . ' (نسخة)');
        $product->sku = $original->sku . '-COPY';
        $product->is_active = false;
        $product->save();

        foreach ($original->images as $img) {
            $product->images()->create([
                'url' => $img->url,
                'is_primary' => $img->is_primary,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product duplicated successfully.',
            'data' => $product->load(['category', 'images']),
        ]);
    }

    public function importCsv(Request $request): JsonResponse
    {
        $request->validate([
            'csv' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv');
        $handle = fopen($file->getPathname(), 'r');
        $headers = fgetcsv($handle);
        $headers = array_map('trim', $headers);
        $expected = ['name', 'sku', 'price', 'stock', 'category', 'description', 'short_description', 'compare_price', 'weight', 'is_featured', 'is_active', 'brand'];

        $rowNumber = 1;
        $imported = 0;
        $errors = [];
        $categoryCache = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $data = array_combine($headers, $row);

            try {
                $name = trim($data['name'] ?? '');
                $sku = trim($data['sku'] ?? '');

                if (empty($name)) { $errors[] = "السطر $rowNumber: الاسم مطلوب"; continue; }
                if (empty($sku)) { $errors[] = "السطر $rowNumber: SKU مطلوب"; continue; }
                if (!is_numeric($data['price'] ?? '')) { $errors[] = "السطر $rowNumber: السعر يجب أن يكون رقماً"; continue; }

                $categoryName = trim($data['category'] ?? '');
                if ($categoryName && !isset($categoryCache[$categoryName])) {
                    $cat = \App\Models\Category::where('name', $categoryName)->first();
                    $categoryCache[$categoryName] = $cat?->id;
                    if (!$cat) { $errors[] = "السطر $rowNumber: التصنيف '$categoryName' غير موجود"; continue; }
                }

                $productData = [
                    'name' => $name,
                    'slug' => Str::slug($name) . '-' . Str::random(4),
                    'sku' => $sku,
                    'description' => trim($data['description'] ?? ''),
                    'short_description' => trim($data['short_description'] ?? ''),
                    'price' => (float) $data['price'],
                    'compare_price' => !empty($data['compare_price']) ? (float) $data['compare_price'] : null,
                    'stock' => (int) ($data['stock'] ?? 0),
                    'weight' => !empty($data['weight']) ? (float) $data['weight'] : null,
                    'is_featured' => in_array(trim($data['is_featured'] ?? ''), ['1', 'true', 'yes', 'نعم']),
                    'is_active' => !isset($data['is_active']) || in_array(trim($data['is_active']), ['1', 'true', 'yes', 'نعم', ''], true),
                    'category_id' => $categoryName ? $categoryCache[$categoryName] : null,
                    'brand' => trim($data['brand'] ?? '') ?: null,
                ];

                $existing = Product::where('sku', $sku)->first();
                if ($existing) {
                    $existing->update($productData);
                } else {
                    Product::create($productData);
                }
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "السطر $rowNumber: " . $e->getMessage();
            }
        }

        fclose($handle);

        return response()->json([
            'success' => true,
            'message' => "تم استيراد $imported منتج بنجاح" . (count($errors) ? " مع $errors[0] أخطاء" : ''),
            'data' => [
                'imported' => $imported,
                'errors' => array_slice($errors, 0, 20),
                'total_errors' => count($errors),
            ],
        ]);
    }

    public function downloadTemplate(): \Illuminate\Http\Response
    {
        $headers = ['name', 'sku', 'price', 'stock', 'category', 'description', 'short_description', 'compare_price', 'weight', 'is_featured', 'is_active', 'brand'];
        $csv = implode(',', $headers) . "\n";
        $csv .= '"مثال منتج","SKU-001",199.00,10,"عطور","وصف المنتج","وصف مختصر",249.00,0.5,1,1,"Chanel"' . "\n";

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="import-template.csv"',
        ]);
    }

    public function bulkToggleActive(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id',
            'is_active' => 'required|boolean',
        ]);

        Product::whereIn('id', $validated['ids'])->update(['is_active' => $validated['is_active']]);

        return response()->json([
            'success' => true,
            'message' => 'Products updated successfully.',
        ]);
    }
}
