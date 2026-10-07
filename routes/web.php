<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PromoApiController;
use App\Http\Controllers\Api\RoleApiController;
use App\Http\Controllers\Api\StockApiController;
use App\Http\Controllers\Api\StoreApiController;
use App\Http\Controllers\Api\AdminApiController;
use App\Http\Controllers\Api\ChatAssistantController;
use App\Http\Controllers\Api\NotificationController;

/*
|--------------------------------------------------------------------------
| Web Routes — PsaOnline Gen-Z Accessories Marketplace
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
*/

// Storefront: the full site lives in public/*.html (home.html, products.html, ...).
// Those files are served directly; "/" just shows the home page.
Route::get('/', fn () => response()->file(public_path('home.html')))->name('home');
Route::get('/home', fn () => response()->file(public_path('home.html')));

// Staff portal pages live in resources/portal/ (not public/), so the web server can't hand
// them out directly; they are only sent to signed-in Staff and Admin accounts.
$portalPages = [
    'dashboard-admin', 'admin-orders', 'admin-products', 'admin-users', 'admin-payment-methods', 'admin-messages', 'admin-promo-codes',
    'order-detail--admin-payment-submitted', 'order-detail--staff-payment-submitted',
    'payment-method-form', 'product-detail--staff', 'product-form', 'user-form',
];
Route::get('/{page}.html', fn (string $page) => response()->file(resource_path("portal/{$page}.html"), [
    'Cache-Control' => 'no-store, private',
]))->whereIn('page', $portalPages)->middleware('staff');

// About & legal pages
Route::get('/about', fn () => response()->file(public_path('about.html')))->name('about');
Route::get('/privacy', fn () => response()->file(public_path('privacy.html')))->name('privacy');
Route::get('/terms', fn () => response()->file(public_path('terms.html')))->name('terms');

// JSON API used by public/assets/js/store.js (session cookie + CSRF header)
Route::prefix('api')->middleware('same-account')->group(function () {
    Route::get('/bootstrap', [StoreApiController::class, 'bootstrap']);
    Route::post('/auth/login', [StoreApiController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/auth/register', [StoreApiController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/auth/logout', [StoreApiController::class, 'logout']);
    Route::patch('/me', [StoreApiController::class, 'updateProfile']);
    Route::put('/me/password', [StoreApiController::class, 'updatePassword']);

    Route::post('/orders', [StoreApiController::class, 'placeOrder'])->middleware('throttle:20,1');
    Route::post('/promo-codes/check', [StoreApiController::class, 'checkPromoCode'])->middleware('throttle:30,1');
    Route::post('/orders/{orderNumber}/slip', [StoreApiController::class, 'uploadSlip']);
    Route::post('/orders/{orderNumber}/demo-paid', [StoreApiController::class, 'markDemoPaid']);

    Route::get('/orders/{orderNumber}/messages', [StoreApiController::class, 'orderMessages']);
    Route::post('/orders/{orderNumber}/messages', [StoreApiController::class, 'sendOrderMessage'])->middleware('throttle:30,1');
    Route::post('/orders/{orderNumber}/reviews', [StoreApiController::class, 'reviewOrder'])->middleware('throttle:20,1');
    Route::get('/products/{sku}/reviews', [StoreApiController::class, 'productReviews']);

    // AI shopping assistant (Gemini) for chat.html
    Route::post('/chat/assistant', [ChatAssistantController::class, 'reply'])->middleware('throttle:10,1');

    // Notification bell + staff Messages inbox
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/admin/conversations', [NotificationController::class, 'conversations']);
    Route::get('/chat/messages', [StoreApiController::class, 'getChatMessages']);
    Route::post('/chat/messages', [StoreApiController::class, 'sendChatMessage'])->middleware('throttle:30,1');

    Route::prefix('admin')->group(function () {
        Route::post('/products', [AdminApiController::class, 'storeProduct']);
        Route::post('/products/move', [AdminApiController::class, 'moveProducts']);
        Route::patch('/products/{sku}', [AdminApiController::class, 'updateProduct']);
        Route::delete('/products/{sku}', [AdminApiController::class, 'destroyProduct']);
        Route::post('/categories', [AdminApiController::class, 'storeCategory']);
        Route::patch('/categories/{category}', [AdminApiController::class, 'updateCategory']);
        Route::delete('/categories/{category}', [AdminApiController::class, 'destroyCategory']);
        Route::put('/showcase', [AdminApiController::class, 'updateShowcase']);
        Route::put('/site-content', [AdminApiController::class, 'updateSiteContent']);
        Route::put('/socials', [AdminApiController::class, 'updateSocials']);
        Route::get('/suppliers', [StockApiController::class, 'suppliers']);
        Route::post('/suppliers', [StockApiController::class, 'storeSupplier']);
        Route::patch('/suppliers/{supplier}', [StockApiController::class, 'updateSupplier']);
        Route::delete('/suppliers/{supplier}', [StockApiController::class, 'destroySupplier']);
        Route::get('/purchase-orders', [StockApiController::class, 'purchaseOrders']);
        Route::post('/purchase-orders', [StockApiController::class, 'storePurchaseOrder']);
        Route::patch('/purchase-orders/{purchaseOrder}', [StockApiController::class, 'updatePurchaseOrder']);
        Route::delete('/purchase-orders/{purchaseOrder}', [StockApiController::class, 'destroyPurchaseOrder']);
        Route::post('/sort-options', [AdminApiController::class, 'storeSortOption']);
        Route::put('/sort-options/order', [AdminApiController::class, 'reorderSortOptions']);
        Route::patch('/sort-options/{sortOption}', [AdminApiController::class, 'updateSortOption']);
        Route::delete('/sort-options/{sortOption}', [AdminApiController::class, 'destroySortOption']);
        Route::patch('/orders/{orderNumber}', [AdminApiController::class, 'updateOrder']);
        Route::post('/users', [AdminApiController::class, 'storeUser']);
        Route::patch('/users/{user}', [AdminApiController::class, 'updateUser']);
        Route::get('/promo-codes', [PromoApiController::class, 'index']);
        Route::post('/promo-codes', [PromoApiController::class, 'store']);
        Route::patch('/promo-codes/{promoCode}', [PromoApiController::class, 'update']);
        Route::delete('/promo-codes/{promoCode}', [PromoApiController::class, 'destroy']);
        Route::get('/roles', [RoleApiController::class, 'index']);
        Route::post('/roles', [RoleApiController::class, 'store']);
        Route::patch('/roles/{role}', [RoleApiController::class, 'update']);
        Route::delete('/roles/{role}', [RoleApiController::class, 'destroy']);
        Route::post('/payment-methods', [AdminApiController::class, 'storePaymentMethod']);
        Route::patch('/payment-methods/{paymentMethod}', [AdminApiController::class, 'updatePaymentMethod']);
        Route::delete('/payment-methods/{paymentMethod}', [AdminApiController::class, 'destroyPaymentMethod']);
    });
});

// Clean addresses: /products serves public/products.html; staff pages (/dashboard-admin, /admin-orders...)
// still require a staff login. The .html addresses keep working too.
Route::get('/{page}', function (Illuminate\Http\Request $request, string $page) use ($portalPages) {
    if (in_array($page, $portalPages, true)) {
        return app(App\Http\Middleware\EnsureStaff::class)->handle($request, fn () => response()->file(
            resource_path("portal/{$page}.html"), ['Cache-Control' => 'no-store, private']
        ));
    }
    $file = public_path("{$page}.html");
    abort_unless(is_file($file), 404);

    return response()->file($file);
})->where('page', '[a-z0-9-]+');
