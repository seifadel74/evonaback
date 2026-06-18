<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function index(): JsonResponse
    {
        $banners = Banner::orderBy('sort_order')->get();
        return response()->json(['success' => true, 'data' => $banners]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title_ar' => 'required|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'subtitle_ar' => 'nullable|string|max:255',
            'subtitle_en' => 'nullable|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
            'link' => 'nullable|string|max:500',
            'btn_text_ar' => 'nullable|string|max:255',
            'btn_text_en' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $path = $request->file('image')->store('banners', 'public');

        $banner = Banner::create([
            'title_ar' => $validated['title_ar'],
            'title_en' => $validated['title_en'] ?? null,
            'subtitle_ar' => $validated['subtitle_ar'] ?? null,
            'subtitle_en' => $validated['subtitle_en'] ?? null,
            'image' => Storage::url($path),
            'link' => $validated['link'] ?? null,
            'btn_text_ar' => $validated['btn_text_ar'] ?? null,
            'btn_text_en' => $validated['btn_text_en'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json(['success' => true, 'data' => $banner], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $banner = Banner::findOrFail($id);

        $validated = $request->validate([
            'title_ar' => 'sometimes|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'subtitle_ar' => 'nullable|string|max:255',
            'subtitle_en' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'link' => 'nullable|string|max:500',
            'btn_text_ar' => 'nullable|string|max:255',
            'btn_text_en' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        if ($request->hasFile('image')) {
            $oldPath = str_replace('/storage/', '', $banner->image);
            Storage::disk('public')->delete($oldPath);
            $validated['image'] = Storage::url($request->file('image')->store('banners', 'public'));
        }

        $banner->update($validated);
        return response()->json(['success' => true, 'data' => $banner]);
    }

    public function destroy(int $id): JsonResponse
    {
        $banner = Banner::findOrFail($id);
        $path = str_replace('/storage/', '', $banner->image);
        Storage::disk('public')->delete($path);
        $banner->delete();
        return response()->json(['success' => true, 'message' => 'تم الحذف']);
    }
}
