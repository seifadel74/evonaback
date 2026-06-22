<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group الطلبات (Orders)
 *
 * @header Content-Type application/json
 * @header Accept application/json
 * @authenticated
 */
class OrderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 20), 100);

        $orders = $request->user()->orders()
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $orders->items(),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $order = $request->user()->orders()
            ->with(['items.product', 'coupon', 'trackingEvents'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $order = $request->user()->orders()->with('items.product')->findOrFail($id);

        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json([
                'success' => false,
                'message' => __('messages.order_cannot_cancel'),
            ], 422);
        }

        DB::transaction(function () use ($order, $request) {
            foreach ($order->items as $item) {
                if ($item->product) {
                    $item->product->increment('stock', $item->quantity);
                }
            }

            if ($order->coupon_id && $order->coupon) {
                $order->coupon->decrement('used_count');
            }

            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $request->input('reason', 'Cancelled by customer'),
            ]);

            \App\Models\OrderTrackingEvent::create([
                'order_id' => $order->id,
                'status' => 'cancelled',
                'notes' => 'ألغى العميل الطلب: ' . ($request->input('reason', 'بدون سبب')),
                'updated_by_type' => 'customer',
                'updated_by_id' => $request->user()->id,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => __('messages.order_cancelled'),
            'data' => $order->fresh(),
        ]);
    }
}
