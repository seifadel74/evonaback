<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Newsletter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email|max:255|unique:newsletters,email',
        ]);

        $newsletter = Newsletter::create([
            'email' => $validated['email'],
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.subscribed'),
            'data' => $newsletter,
        ], 201);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|string|email|exists:newsletters,email',
        ]);

        Newsletter::where('email', $validated['email'])->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => __('messages.unsubscribed'),
        ]);
    }
}
