<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnnouncementBar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnnouncementBarController extends Controller
{
    public function index(): JsonResponse
    {
        $bars = AnnouncementBar::orderBy('sort_order')->orderBy('created_at', 'desc')->get();
        return response()->json(['success' => true, 'data' => $bars]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|max:500',
            'link' => 'nullable|string|max:500',
            'bg_color' => 'nullable|string|max:20',
            'text_color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'sort_order' => 'nullable|integer',
        ]);

        $bar = AnnouncementBar::create($validated);
        return response()->json(['success' => true, 'data' => $bar], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $bar = AnnouncementBar::findOrFail($id);
        $validated = $request->validate([
            'text' => 'sometimes|string|max:500',
            'link' => 'nullable|string|max:500',
            'bg_color' => 'nullable|string|max:20',
            'text_color' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'sort_order' => 'nullable|integer',
        ]);

        $bar->update($validated);
        return response()->json(['success' => true, 'data' => $bar]);
    }

    public function destroy(int $id): JsonResponse
    {
        AnnouncementBar::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    public function active(): JsonResponse
    {
        $bars = AnnouncementBar::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('sort_order')
            ->get();
        return response()->json(['success' => true, 'data' => $bars]);
    }
}
