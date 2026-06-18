<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\Admin\ProductVariantController;

// SEO
Route::get('/robots.txt', [App\Http\Controllers\Api\SeoController::class, 'robots']);
Route::get('/sitemap.xml', [App\Http\Controllers\Api\SeoController::class, 'sitemap']);

Route::post('/v1/checkout/webhook/stripe', [CheckoutController::class, 'webhook']);

Route::prefix('v1')->group(function () {

    // Public routes
    Route::post('/auth/register', [RegisterController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/auth/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::post('/auth/forgot-password', [App\Http\Controllers\Api\Auth\ForgotPasswordController::class, 'forgot'])->middleware('throttle:5,1');
    Route::post('/auth/reset-password', [App\Http\Controllers\Api\Auth\ForgotPasswordController::class, 'reset'])->middleware('throttle:5,1');

    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/filters', [ProductController::class, 'filters']);
    Route::get('/products/featured', [ProductController::class, 'featured']);
    Route::get('/products/search', [ProductController::class, 'search']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);

    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{slug}', [CategoryController::class, 'show']);

    Route::get('/products/{slug}/reviews', [ReviewController::class, 'index']);

    // Public banners
    Route::get('/banners/active', [App\Http\Controllers\Api\BannerController::class, 'active']);

    // Public announcement bars
    Route::get('/announcements/active', [App\Http\Controllers\Api\Admin\AnnouncementBarController::class, 'active']);

    // Public contact
    Route::post('/contact', [ContactController::class, 'send'])->middleware('throttle:5,1');

    // Public newsletter
    Route::post('/newsletter/subscribe', [App\Http\Controllers\Api\NewsletterController::class, 'subscribe'])->middleware('throttle:10,1');
    Route::post('/newsletter/unsubscribe', [App\Http\Controllers\Api\NewsletterController::class, 'unsubscribe'])->middleware('throttle:10,1');

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [LogoutController::class, 'destroy']);
        Route::get('/auth/me', [ProfileController::class, 'show']);
        Route::put('/auth/profile', [ProfileController::class, 'update']);
        Route::post('/auth/upload-avatar', [ProfileController::class, 'uploadAvatar']);

        Route::get('/cart', [CartController::class, 'show']);
        Route::post('/cart/items', [CartController::class, 'addItem']);
        Route::put('/cart/items/{id}', [CartController::class, 'updateItem']);
        Route::delete('/cart/items/{id}', [CartController::class, 'removeItem']);

        Route::get('/wishlist', [WishlistController::class, 'index']);
        Route::post('/wishlist', [WishlistController::class, 'toggle']);

        Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:20,1');
        Route::get('/checkout/preview', [CheckoutController::class, 'preview']);
        Route::post('/checkout/apply-coupon', [CouponController::class, 'validateCoupon']);

        Route::get('/addresses', [App\Http\Controllers\Api\AddressController::class, 'index']);
        Route::post('/addresses', [App\Http\Controllers\Api\AddressController::class, 'store']);
        Route::get('/addresses/{id}', [App\Http\Controllers\Api\AddressController::class, 'show']);
        Route::put('/addresses/{id}', [App\Http\Controllers\Api\AddressController::class, 'update']);
        Route::delete('/addresses/{id}', [App\Http\Controllers\Api\AddressController::class, 'destroy']);

        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}', [OrderController::class, 'show']);
        Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);

        Route::post('/reviews', [ReviewController::class, 'store']);
        Route::put('/reviews/{review}', [ReviewController::class, 'update']);
        Route::delete('/reviews/{review}', [ReviewController::class, 'destroy']);

        Route::post('/coupons/validate', [CouponController::class, 'validateCoupon']);

        Route::get('/returns', [App\Http\Controllers\Api\ReturnRequestController::class, 'index']);
        Route::post('/returns', [App\Http\Controllers\Api\ReturnRequestController::class, 'store']);
        Route::get('/returns/{id}', [App\Http\Controllers\Api\ReturnRequestController::class, 'show']);
    });

    // Admin routes
    Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function () {
        Route::get('/dashboard/stats', [App\Http\Controllers\Api\Admin\DashboardController::class, 'stats']);
        Route::get('/dashboard/charts', [App\Http\Controllers\Api\Admin\DashboardController::class, 'charts']);

        Route::get('/products/export/csv', [App\Http\Controllers\Api\Admin\ProductController::class, 'exportCsv']);
        Route::post('/products/import/csv', [App\Http\Controllers\Api\Admin\ProductController::class, 'importCsv']);
        Route::get('/products/import/template', [App\Http\Controllers\Api\Admin\ProductController::class, 'downloadTemplate']);
        Route::get('/products/trashed/all', [App\Http\Controllers\Api\Admin\ProductController::class, 'trashed']);
        Route::put('/products/{id}/restore', [App\Http\Controllers\Api\Admin\ProductController::class, 'restore']);
        Route::post('/products/{id}/duplicate', [App\Http\Controllers\Api\Admin\ProductController::class, 'duplicate']);
        Route::put('/products/bulk/toggle-active', [App\Http\Controllers\Api\Admin\ProductController::class, 'bulkToggleActive']);
        Route::apiResource('/products', App\Http\Controllers\Api\Admin\ProductController::class);
        Route::apiResource('/categories', App\Http\Controllers\Api\Admin\CategoryController::class);
        Route::get('/orders/export/csv', [App\Http\Controllers\Api\Admin\OrderController::class, 'exportCsv']);
        Route::apiResource('/orders', App\Http\Controllers\Api\Admin\OrderController::class)->except(['store', 'destroy']);
        Route::put('/orders/{id}/status', [App\Http\Controllers\Api\Admin\OrderController::class, 'updateStatus']);
        Route::put('/orders/{id}/payment-status', [App\Http\Controllers\Api\Admin\OrderController::class, 'updatePaymentStatus']);
        Route::post('/orders/{id}/tracking', [App\Http\Controllers\Api\Admin\OrderController::class, 'addTrackingEvent']);
        Route::get('/customers', [App\Http\Controllers\Api\Admin\CustomerController::class, 'index']);
        Route::get('/customers/{id}', [App\Http\Controllers\Api\Admin\CustomerController::class, 'show']);
        Route::delete('/customers/{id}', [App\Http\Controllers\Api\Admin\CustomerController::class, 'destroy']);
        Route::put('/inventory', [App\Http\Controllers\Api\Admin\InventoryController::class, 'updateBatch']);
        Route::apiResource('/coupons', App\Http\Controllers\Api\Admin\CouponController::class);
        Route::put('/coupons/{id}/toggle-active', [App\Http\Controllers\Api\Admin\CouponController::class, 'toggleActive']);

        Route::post('/images', [App\Http\Controllers\Api\Admin\ImageController::class, 'store']);
        Route::post('/images/from-url', [App\Http\Controllers\Api\Admin\ImageController::class, 'storeFromUrl']);
        Route::put('/images/{id}', [App\Http\Controllers\Api\Admin\ImageController::class, 'update']);
        Route::delete('/images/{id}', [App\Http\Controllers\Api\Admin\ImageController::class, 'destroy']);

        Route::get('/reviews', [App\Http\Controllers\Api\Admin\ReviewController::class, 'index']);
        Route::put('/reviews/{id}/approve', [App\Http\Controllers\Api\Admin\ReviewController::class, 'approve']);
        Route::delete('/reviews/{id}', [App\Http\Controllers\Api\Admin\ReviewController::class, 'destroy']);

        Route::get('/contact-messages', [App\Http\Controllers\Api\Admin\ContactMessageController::class, 'index']);
        Route::get('/contact-messages/{id}', [App\Http\Controllers\Api\Admin\ContactMessageController::class, 'show']);
        Route::delete('/contact-messages/{id}', [App\Http\Controllers\Api\Admin\ContactMessageController::class, 'destroy']);

        Route::get('/banners', [App\Http\Controllers\Api\Admin\BannerController::class, 'index']);
        Route::post('/banners', [App\Http\Controllers\Api\Admin\BannerController::class, 'store']);
        Route::put('/banners/{id}', [App\Http\Controllers\Api\Admin\BannerController::class, 'update']);
        Route::delete('/banners/{id}', [App\Http\Controllers\Api\Admin\BannerController::class, 'destroy']);

        Route::get('/announcements', [App\Http\Controllers\Api\Admin\AnnouncementBarController::class, 'index']);
        Route::post('/announcements', [App\Http\Controllers\Api\Admin\AnnouncementBarController::class, 'store']);
        Route::put('/announcements/{id}', [App\Http\Controllers\Api\Admin\AnnouncementBarController::class, 'update']);
        Route::delete('/announcements/{id}', [App\Http\Controllers\Api\Admin\AnnouncementBarController::class, 'destroy']);

        Route::get('/products/{product}/attributes', [ProductVariantController::class, 'attributes']);
        Route::post('/products/{product}/attributes', [ProductVariantController::class, 'storeAttribute']);
        Route::put('/product-attributes/{attribute}', [ProductVariantController::class, 'updateAttribute']);
        Route::delete('/product-attributes/{attribute}', [ProductVariantController::class, 'deleteAttribute']);
        Route::post('/product-attributes/{attribute}/values', [ProductVariantController::class, 'storeAttributeValue']);
        Route::put('/product-attribute-values/{attributeValue}', [ProductVariantController::class, 'updateAttributeValue']);
        Route::delete('/product-attribute-values/{attributeValue}', [ProductVariantController::class, 'deleteAttributeValue']);
        Route::get('/products/{product}/variants', [ProductVariantController::class, 'variants']);
        Route::post('/products/{product}/variants', [ProductVariantController::class, 'storeVariant']);
        Route::put('/product-variants/{variant}', [ProductVariantController::class, 'updateVariant']);
        Route::delete('/product-variants/{variant}', [ProductVariantController::class, 'deleteVariant']);

        Route::get('/return-requests', [App\Http\Controllers\Api\Admin\ReturnRequestController::class, 'index']);
        Route::get('/return-requests/{id}', [App\Http\Controllers\Api\Admin\ReturnRequestController::class, 'show']);
        Route::put('/return-requests/{id}', [App\Http\Controllers\Api\Admin\ReturnRequestController::class, 'update']);
        Route::delete('/return-requests/{id}', [App\Http\Controllers\Api\Admin\ReturnRequestController::class, 'destroy']);
    });
});
