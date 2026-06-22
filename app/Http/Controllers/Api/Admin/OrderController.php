<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTrackingEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function exportCsv(Request $request): \Illuminate\Http\Response
    {
        $query = Order::with('user:id,name,email');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderBy('created_at', 'desc')->limit(1000)->get();

        $headers = ['رقم الطلب', 'العميل', 'البريد', 'المجموع', 'الحالة', 'طريقة الدفع', 'تاريخ'];
        $rows = $orders->map(fn($o) => [
            $o->order_number,
            $o->user?->name ?? '',
            $o->user?->email ?? '',
            $o->total,
            $o->status,
            $o->payment_method ?? 'stripe',
            $o->created_at->format('Y-m-d'),
        ]);

        $csv = implode(',', $headers) . "\n";
        foreach ($rows as $row) {
            $csv .= implode(',', array_map(fn($v) => '"' . str_replace('"', '""', $v) . '"', $row)) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="orders.csv"',
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Order::with('user:id,name,email');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('order_number', 'like', '%' . $request->search . '%')
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%' . $request->search . '%'));
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

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

    public function show(int $id): JsonResponse
    {
        $order = Order::with(['user', 'items.product', 'coupon', 'trackingEvents'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    public function updatePaymentStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'payment_status' => 'required|string|in:pending,paid,failed,refunded',
        ]);

        $order = Order::findOrFail($id);
        $order->update(['payment_status' => $validated['payment_status']]);

        OrderTrackingEvent::create([
            'order_id' => $order->id,
            'status' => $order->status,
            'notes' => 'تم تحديث حالة الدفع إلى ' . $validated['payment_status'],
            'updated_by_type' => 'admin',
            'updated_by_id' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.payment_status_updated'),
            'data' => $order->fresh()->load('user', 'items', 'trackingEvents'),
        ]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded',
            'notes' => 'nullable|string|max:500',
            'carrier' => 'nullable|string|max:255',
            'tracking_number' => 'nullable|string|max:255',
        ]);

        $order = Order::findOrFail($id);

        $timestamps = [
            'confirmed' => 'confirmed_at',
            'processing' => 'processing_at',
            'shipped' => 'shipped_at',
            'delivered' => 'delivered_at',
            'cancelled' => 'cancelled_at',
            'refunded' => 'refunded_at',
        ];

        $data = ['status' => $validated['status']];

        if (isset($timestamps[$validated['status']])) {
            $data[$timestamps[$validated['status']]] = now();
        }

        if ($request->filled('carrier')) {
            $data['carrier'] = $validated['carrier'];
        }

        if ($request->filled('tracking_number')) {
            $data['tracking_number'] = $validated['tracking_number'];
        }

        $order->update($data);

        OrderTrackingEvent::create([
            'order_id' => $order->id,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'carrier' => $validated['carrier'] ?? null,
            'tracking_number' => $validated['tracking_number'] ?? null,
            'updated_by_type' => 'admin',
            'updated_by_id' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.order_status_updated'),
            'data' => $order->fresh()->load('user', 'items', 'trackingEvents'),
        ]);
    }

    public function addTrackingEvent(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:pending,confirmed,processing,shipped,delivered,cancelled,refunded',
            'notes' => 'nullable|string|max:500',
            'carrier' => 'nullable|string|max:255',
            'tracking_number' => 'nullable|string|max:255',
        ]);

        $order = Order::findOrFail($id);

        $event = OrderTrackingEvent::create([
            'order_id' => $order->id,
            'status' => $validated['status'],
            'notes' => $validated['notes'] ?? null,
            'carrier' => $validated['carrier'] ?? null,
            'tracking_number' => $validated['tracking_number'] ?? null,
            'updated_by_type' => 'admin',
            'updated_by_id' => $request->user()?->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('messages.tracking_event_added'),
            'data' => $event,
        ]);
    }
}
