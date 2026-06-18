<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Mail\OrderConfirmationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session;
use Stripe\Coupon as StripeCoupon;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;

class StripeService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    public function createCheckoutSession(Order $order, User $user): mixed
    {
        $lineItems = [];

        foreach ($order->items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'egp',
                    'product_data' => [
                        'name' => $item->product_name,
                    ],
                    'unit_amount' => (int) round($item->product_price * 100),
                ],
                'quantity' => $item->quantity,
            ];
        }

        if ($order->shipping_cost > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'egp',
                    'product_data' => [
                        'name' => 'Shipping',
                    ],
                    'unit_amount' => (int) round($order->shipping_cost * 100),
                ],
                'quantity' => 1,
            ];
        }

        if ($order->tax > 0) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'egp',
                    'product_data' => [
                        'name' => 'Tax',
                    ],
                    'unit_amount' => (int) round($order->tax * 100),
                ],
                'quantity' => 1,
            ];
        }

        $sessionParams = [
            'mode' => 'payment',
            'client_reference_id' => (string) $order->id,
            'customer_email' => $user->email,
            'line_items' => $lineItems,
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ],
            'success_url' => config('app.frontend_url') . '/order-success?session_id={CHECKOUT_SESSION_ID}&order_id=' . $order->id . '&order_number=' . urlencode($order->order_number),
            'cancel_url' => config('app.frontend_url') . '/order-cancel',
        ];

        if ($order->discount > 0) {
            $stripeCoupon = StripeCoupon::create([
                'amount_off' => (int) round($order->discount * 100),
                'currency' => 'egp',
                'duration' => 'once',
                'name' => 'Order discount',
            ]);
            $sessionParams['discounts'] = [['coupon' => $stripeCoupon->id]];
        }

        $checkout = Session::create($sessionParams);

        $order->update([
            'payment_session_id' => $checkout->id,
        ]);

        return $checkout;
    }

    public function handleWebhook(Request $request): array
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $endpointSecret = config('services.stripe.webhook.secret');
        $tolerance = config('services.stripe.webhook.tolerance', 300);

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $endpointSecret, $tolerance);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', ['error' => $e->getMessage()]);

            return ['success' => false, 'error' => 'Invalid signature.', 'code' => 400];
        }

        return match ($event->type) {
            'checkout.session.completed' => $this->handleSessionCompleted($event->data->object),
            'checkout.session.expired' => $this->handleSessionExpired($event->data->object),
            default => ['success' => true, 'message' => 'Unhandled event type.']
        };
    }

    protected function handleSessionCompleted(mixed $session): array
    {
        $order = Order::with('coupon')->find((int) $session->metadata->order_id);

        if (!$order) {
            return ['success' => false, 'error' => 'Order not found.', 'code' => 404];
        }

        if ($order->status !== 'pending') {
            return ['success' => true, 'message' => 'Order already processed.'];
        }

        if ($session->payment_status !== 'paid') {
            Log::warning('Stripe session completed but not paid', ['order_id' => $order->id, 'payment_status' => $session->payment_status]);

            return ['success' => false, 'error' => 'Payment not completed.', 'code' => 400];
        }

        if ($order->payment_session_id && $order->payment_session_id !== $session->id) {
            Log::warning('Stripe session ID mismatch', ['order_id' => $order->id, 'expected' => $order->payment_session_id, 'received' => $session->id]);

            return ['success' => false, 'error' => 'Session mismatch.', 'code' => 400];
        }

        $expectedAmount = (int) round($order->total * 100);
        if ($session->amount_total !== $expectedAmount) {
            Log::warning('Stripe amount mismatch', [
                'order_id' => $order->id,
                'expected' => $expectedAmount,
                'received' => $session->amount_total,
            ]);

            return ['success' => false, 'error' => 'Amount mismatch.', 'code' => 400];
        }

        $order->update([
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_intent_id' => $session->payment_intent,
            'payment_method' => $session->payment_method_types[0] ?? 'stripe',
            'paid_at' => now(),
            'confirmed_at' => now(),
        ]);

        if ($order->coupon_id && $order->coupon) {
            $order->coupon->increment('used_count');
        }

        try { \Illuminate\Support\Facades\Mail::to($order->user->email)->send(new OrderConfirmationMail($order->fresh()->load(['user', 'items']))); } catch (\Throwable $e) { \Illuminate\Support\Facades\Log::warning('Failed to send order confirmation', ['order' => $order->id, 'error' => $e->getMessage()]); }

        return ['success' => true, 'message' => 'Payment confirmed.'];
    }

    protected function handleSessionExpired(mixed $session): array
    {
        $order = Order::find((int) $session->metadata->order_id);

        if ($order && $order->status === 'pending') {
            $this->restoreStock($order);
            $order->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancellation_reason' => 'Payment session expired.']);
        }

        return ['success' => true, 'message' => 'Session expired handled.'];
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
