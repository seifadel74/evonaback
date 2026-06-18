<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\CheckoutPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group الكوبونات (Coupons)
 *
 * @header Content-Type application/json
 * @header Accept application/json
 */
class CouponController extends Controller
{
    public function validateCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'cart_total' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', $validated['code'])->first();

        if (!$coupon || !CheckoutPricing::isCouponValid($coupon, (float) $validated['cart_total'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid coupon code.',
            ], 422);
        }

        $discount = $coupon->type === 'percentage'
            ? round($validated['cart_total'] * $coupon->value / 100, 2)
            : min($coupon->value, $validated['cart_total']);

        return response()->json([
            'success' => true,
            'data' => [
                'coupon' => $coupon,
                'discount' => $discount,
            ],
        ]);
    }
}
