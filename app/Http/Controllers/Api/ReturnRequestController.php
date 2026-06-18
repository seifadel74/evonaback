<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ReturnRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReturnRequestController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $returns = $request->user()->returnRequests()
            ->with(['order', 'items.orderItem'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $returns->items(),
            'meta' => [
                'current_page' => $returns->currentPage(),
                'last_page' => $returns->lastPage(),
                'total' => $returns->total(),
                'per_page' => $returns->perPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'reason' => 'required|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|integer|exists:order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.reason' => 'nullable|string|max:1000',
        ]);

        $order = Order::where('user_id', $request->user()->id)
            ->with('items')
            ->findOrFail($validated['order_id']);

        if (!in_array($order->status, ['delivered', 'shipped', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تقديم طلب إرجاع لهذا الطلب.',
            ], 422);
        }

        $existing = $order->returnRequests()->whereIn('status', ['pending', 'approved', 'items_received'])->exists();
        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد طلب إرجاع قيد المعالجة لهذا الطلب بالفعل.',
            ], 422);
        }

        $return = $request->user()->returnRequests()->create([
            'return_number' => 'RET-' . strtoupper(Str::random(10)),
            'order_id' => $order->id,
            'status' => 'pending',
            'reason' => $validated['reason'],
        ]);

        foreach ($validated['items'] as $itemData) {
            $orderItem = $order->items()->findOrFail($itemData['order_item_id']);
            if ($itemData['quantity'] > $orderItem->quantity) {
                $return->delete();
                return response()->json([
                    'success' => false,
                    'message' => 'الكمية المطلوبة للإرجاع أكبر من الكمية المشتراة.',
                ], 422);
            }

            $return->items()->create([
                'order_item_id' => $itemData['order_item_id'],
                'quantity' => $itemData['quantity'],
                'reason' => $itemData['reason'] ?? null,
            ]);
        }

        $order->trackingEvents()->create([
            'status' => 'return_requested',
            'notes' => 'تم تقديم طلب إرجاع #' . $return->return_number,
            'updated_by_type' => 'customer',
            'updated_by_id' => $request->user()->id,
        ]);

        $return->load(['order', 'items.orderItem']);
        return response()->json([
            'success' => true,
            'message' => 'تم تقديم طلب الإرجاع بنجاح.',
            'data' => $return,
        ], 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $return = $request->user()->returnRequests()
            ->with(['order.items', 'items.orderItem'])
            ->findOrFail($id);
        return response()->json(['success' => true, 'data' => $return]);
    }
}
