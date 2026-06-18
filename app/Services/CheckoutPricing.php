<?php

namespace App\Services;

use App\Models\Coupon;

class CheckoutPricing
{
    public const SHIPPING_THRESHOLD = 200;

    public const SHIPPING_COST = 25;

    public const TAX_RATE = 0.15;

    public static function calculate(float $subtotal, ?Coupon $coupon = null): array
    {
        $discount = 0;
        $couponId = null;

        if ($coupon && self::isCouponValid($coupon, $subtotal)) {
            $discount = $coupon->type === 'percentage'
                ? round($subtotal * $coupon->value / 100, 2)
                : min($coupon->value, $subtotal);
            $couponId = $coupon->id;
        }

        $shippingCost = $subtotal >= self::SHIPPING_THRESHOLD ? 0 : self::SHIPPING_COST;
        $tax = round($subtotal * self::TAX_RATE, 2);
        $total = round($subtotal + $shippingCost + $tax - $discount, 2);

        return [
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'tax' => $tax,
            'discount' => $discount,
            'total' => $total,
            'coupon_id' => $couponId,
        ];
    }

    public static function isCouponValid(Coupon $coupon, float $subtotal): bool
    {
        if (!$coupon->is_active) {
            return false;
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            return false;
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return false;
        }

        if ($coupon->max_uses && $coupon->used_count >= $coupon->max_uses) {
            return false;
        }

        if ($coupon->min_order_amount && $subtotal < $coupon->min_order_amount) {
            return false;
        }

        return true;
    }

    public static function findValidCoupon(?string $code, float $subtotal): ?Coupon
    {
        if (!$code) {
            return null;
        }

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon || !self::isCouponValid($coupon, $subtotal)) {
            return null;
        }

        return $coupon;
    }
}
