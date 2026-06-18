<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @group لوحة التحكم (Admin)
 *
 * @header Content-Type application/json
 * @header Accept application/json
 * @authenticated
 */
class DashboardController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        $days = match ($request->period) {
            '7' => 7,
            '90' => 90,
            '365' => 365,
            default => 30,
        };

        $since = Carbon::now()->subDays($days);

        $totalRevenue = Order::where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $since)
            ->sum('total');
        $totalOrders = Order::where('created_at', '>=', $since)->count();
        $totalProducts = Product::count();
        $totalCustomers = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'admin'))->count();
        $pendingOrders = Order::where('status', 'pending')->where('created_at', '>=', $since)->count();
        $processingOrders = Order::where('status', 'processing')->where('created_at', '>=', $since)->count();
        $shippedOrders = Order::where('status', 'shipped')->where('created_at', '>=', $since)->count();

        $monthlyRevenue = Order::where('status', '!=', 'cancelled')
            ->where('created_at', '>=', $since)
            ->sum('total');

        return response()->json([
            'success' => true,
            'data' => [
                'total_revenue' => $totalRevenue,
                'total_orders' => $totalOrders,
                'total_products' => $totalProducts,
                'total_customers' => $totalCustomers,
                'pending_orders' => $pendingOrders,
                'processing_orders' => $processingOrders,
                'shipped_orders' => $shippedOrders,
                'monthly_revenue' => $monthlyRevenue,
            ],
        ]);
    }

    public function charts(Request $request): JsonResponse
    {
        $days = match ($request->period) {
            '7' => 7,
            '90' => 90,
            '365' => 365,
            default => 30,
        };

        $dailySales = Order::where('status', '!=', 'cancelled')
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total) as total'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $salesByCategory = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('categories.name', DB::raw('SUM(order_items.subtotal) as total'))
            ->groupBy('categories.name')
            ->get();

        $recentOrders = Order::with('user:id,name')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'daily_sales' => $dailySales,
                'sales_by_category' => $salesByCategory,
                'recent_orders' => $recentOrders,
            ],
        ]);
    }
}
