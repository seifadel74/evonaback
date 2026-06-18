<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReturnRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReturnRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ReturnRequest::with(['order', 'user', 'items.orderItem'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min($request->integer('per_page', 20), 50);
        $returns = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $returns->items(),
            'meta' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'per_page' => $returns->perPage(),
                'total' => $returns->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $return = ReturnRequest::with(['order.items', 'user', 'items.orderItem'])->findOrFail($id);
        return response()->json(['success' => true, 'data' => $return]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,items_received,refunded',
            'admin_notes' => 'nullable|string|max:2000',
            'refund_amount' => 'nullable|numeric|min:0',
        ]);

        $return = ReturnRequest::with('order')->findOrFail($id);

        $return->update($validated);

        if (in_array($validated['status'], ['approved', 'rejected', 'refunded'])) {
            $statusMap = [
                'approved' => 'return_approved',
                'rejected' => 'return_rejected',
                'items_received' => 'items_received',
                'refunded' => 'refunded',
            ];
            $return->order->trackingEvents()->create([
                'status' => $statusMap[$validated['status']],
                'notes' => 'طلب الإرجاع #' . $return->return_number . ': ' . ($validated['admin_notes'] ?? 'تم تحديث الحالة'),
                'updated_by_type' => 'admin',
                'updated_by_id' => $request->user()->id,
            ]);

            if ($validated['status'] === 'refunded') {
                $return->order->update(['status' => 'refunded']);
            }
        }

        $return->load(['order', 'user', 'items.orderItem']);
        return response()->json(['success' => true, 'data' => $return]);
    }

    public function destroy(int $id): JsonResponse
    {
        ReturnRequest::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
