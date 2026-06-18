<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Services\CheckoutPricing;
use App\Services\StripeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * @group الدفع (Checkout)
 *
 * @header Content-Type application/json
 * @header Accept application/json
 * @authenticated
 */
class CheckoutController extends Controller
{
    public function __construct(
        protected StripeService $stripeService
    ) {}

    public function preview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'coupon_code' => 'nullable|string|max:50',
        ]);

        $cart = Cart::with('items.product')
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $subtotal = $this->calculateSubtotal($cart);
        $coupon = CheckoutPricing::findValidCoupon($validated['coupon_code'] ?? null, $subtotal);
        $pricing = CheckoutPricing::calculate($subtotal, $coupon);

        return response()->json([
            'success' => true,
            'data' => $pricing,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'shipping_address' => 'required|array',
            'shipping_address.address_line1' => 'required|string',
            'shipping_address.address_line2' => 'nullable|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.state' => 'nullable|string',
            'shipping_address.postal_code' => 'nullable|string',
            'shipping_address.country' => 'required|string',
            'billing_address' => 'nullable|array',
            'coupon_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
            'payment_method' => 'nullable|string|in:stripe,cod',
        ]);

        $paymentMethod = $validated['payment_method'] ?? 'stripe';

        try {
            $order = DB::transaction(function () use ($request, $validated, $paymentMethod) {
                $cart = Cart::with(['items.product', 'items.variant'])
                    ->where('user_id', $request->user()->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($cart->items->isEmpty()) {
                    throw new \RuntimeException('Cart is empty.');
                }

                $subtotal = 0;
                foreach ($cart->items as $item) {
                    $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

                    if (!$product || !$product->is_active) {
                        throw new \RuntimeException("Product {$item->product_id} is unavailable.");
                    }

                    $stock = $item->variant ? $item->variant->stock : $product->stock;
                    if ($stock < $item->quantity) {
                        throw new \RuntimeException("Insufficient stock for {$product->name}.");
                    }

                    $price = $item->variant?->price ?? $product->price;
                    $subtotal += $price * $item->quantity;
                }

                $coupon = CheckoutPricing::findValidCoupon($validated['coupon_code'] ?? null, $subtotal);
                $pricing = CheckoutPricing::calculate($subtotal, $coupon);

                $order = Order::create([
                    'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                    'user_id' => $request->user()->id,
                    'status' => $paymentMethod === 'cod' ? 'confirmed' : 'pending',
                    'subtotal' => $pricing['subtotal'],
                    'shipping_cost' => $pricing['shipping_cost'],
                    'tax' => $pricing['tax'],
                    'discount' => $pricing['discount'],
                    'total' => $pricing['total'],
                    'coupon_id' => $pricing['coupon_id'],
                    'shipping_address' => $validated['shipping_address'],
                    'billing_address' => $validated['billing_address'] ?? $validated['shipping_address'],
                    'notes' => $validated['notes'] ?? null,
                    'payment_method' => $paymentMethod,
                    'payment_status' => 'pending',
                ]);

                foreach ($cart->items as $item) {
                    $product = Product::where('id', $item->product_id)->lockForUpdate()->first();
                    $price = $item->variant?->price ?? $product->price;

                    $variantDetails = null;
                    if ($item->variant) {
                        $item->variant->load('attributeValues');
                        $variantDetails = [
                            'variant_id' => $item->variant->id,
                            'sku' => $item->variant->sku,
                            'attributes' => $item->variant->attributeValues->pluck('value')->toArray(),
                        ];
                    }

                    $order->items()->create([
                        'product_id' => $item->product_id,
                        'product_name' => $product->name,
                        'product_price' => $price,
                        'quantity' => $item->quantity,
                        'subtotal' => $price * $item->quantity,
                        'variant_details' => $variantDetails ? json_encode($variantDetails) : null,
                    ]);

                    $product->decrement('stock', $item->quantity);

                    if ($item->variant) {
                        $item->variant->decrement('stock', $item->quantity);
                    }
                }

                if ($paymentMethod === 'cod' && $coupon) {
                    $coupon->increment('used_count');
                }

                $cart->items()->delete();

                return $order->fresh()->load('items');
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        if ($paymentMethod === 'cod') {
            $order->load('user');
            try { \Illuminate\Support\Facades\Mail::to($order->user->email)->send(new OrderConfirmationMail($order)); } catch (\Throwable $e) { \Illuminate\Support\Facades\Log::warning('Failed to send order confirmation', ['order' => $order->id, 'error' => $e->getMessage()]); }
            return response()->json([
                'success' => true,
                'message' => 'Order created successfully. Pay on delivery.',
                'data' => [
                    'order' => $order,
                    'payment_url' => null,
                ],
            ], 201);
        }

        try {
            $session = $this->stripeService->createCheckoutSession($order, $request->user());
        } catch (\Exception $e) {
            Log::error('Stripe checkout session failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
            $this->restoreStock($order);
            $order->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancellation_reason' => 'Payment initiation failed.']);

            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order created. Redirect to payment.',
            'data' => [
                'order' => $order->fresh()->load('items'),
                'payment_url' => $session->url,
                'session_id' => $session->id,
            ],
        ], 201);
    }

    public function webhook(Request $request): JsonResponse
    {
        $result = $this->stripeService->handleWebhook($request);

        if (!$result['success']) {
            return response()->json(
                ['success' => false, 'message' => $result['error'] ?? 'Webhook failed.'],
                $result['code'] ?? 400
            );
        }

        return response()->json(['success' => true, 'message' => $result['message'] ?? 'OK']);
    }

    protected function calculateSubtotal(Cart $cart): float
    {
        $subtotal = 0;
        foreach ($cart->items as $item) {
            $subtotal += $item->product->price * $item->quantity;
        }

        return $subtotal;
    }

    protected function restoreStock(Order $order): void
    {
        foreach ($order->items as $item) {
            if ($item->product) {
                $item->product->increment('stock', $item->quantity);
            }
        }
    }
}
