<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuyerDashboardController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminPaymentMethodController;
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
    'dashboard-admin', 'admin-orders', 'admin-products', 'admin-users', 'admin-payment-methods', 'admin-messages',
    'order-detail--admin-payment-submitted', 'order-detail--staff-payment-submitted',
    'payment-method-form', 'product-detail--staff', 'product-form', 'user-form',
];
Route::get('/{page}.html', fn (string $page) => response()->file(resource_path("portal/{$page}.html"), [
    'Cache-Control' => 'no-store, private',
]))->whereIn('page', $portalPages)->middleware('staff');

// About & legal pages (named so the old Blade layout can link to them)
Route::get('/about', fn () => response()->file(public_path('about.html')))->name('about');
Route::get('/privacy', fn () => response()->file(public_path('privacy.html')))->name('privacy');
Route::get('/terms', fn () => response()->file(public_path('terms.html')))->name('terms');

// JSON API used by public/assets/js/store.js (session cookie + CSRF header)
Route::prefix('api')->group(function () {
    Route::get('/bootstrap', [StoreApiController::class, 'bootstrap']);
    Route::post('/auth/login', [StoreApiController::class, 'login'])->middleware('throttle:10,1');
    Route::post('/auth/register', [StoreApiController::class, 'register'])->middleware('throttle:10,1');
    Route::post('/auth/logout', [StoreApiController::class, 'logout']);
    Route::patch('/me', [StoreApiController::class, 'updateProfile']);
    Route::put('/me/password', [StoreApiController::class, 'updatePassword']);

    Route::post('/orders', [StoreApiController::class, 'placeOrder'])->middleware('throttle:20,1');
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
    Route::post('/notifications/read', [NotificationController::class, 'markRead']);
    Route::get('/admin/conversations', [NotificationController::class, 'conversations']);
    Route::get('/chat/messages', [StoreApiController::class, 'getChatMessages']);
    Route::post('/chat/messages', [StoreApiController::class, 'sendChatMessage'])->middleware('throttle:30,1');

    Route::prefix('admin')->group(function () {
        Route::post('/products', [AdminApiController::class, 'storeProduct']);
        Route::patch('/products/{sku}', [AdminApiController::class, 'updateProduct']);
        Route::delete('/products/{sku}', [AdminApiController::class, 'destroyProduct']);
        Route::patch('/orders/{orderNumber}', [AdminApiController::class, 'updateOrder']);
        Route::post('/users', [AdminApiController::class, 'storeUser']);
        Route::patch('/users/{user}', [AdminApiController::class, 'updateUser']);
        Route::post('/payment-methods', [AdminApiController::class, 'storePaymentMethod']);
        Route::patch('/payment-methods/{paymentMethod}', [AdminApiController::class, 'updatePaymentMethod']);
        Route::delete('/payment-methods/{paymentMethod}', [AdminApiController::class, 'destroyPaymentMethod']);
    });
});

// Old Blade versions of the shop and admin (before the HTML storefront). Kept under /legacy so their
// route names still work, while /products, /cart, /login... serve the real pages below.
Route::prefix('legacy')->group(function () {
    // Product Drops & Catalog
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductController::class, 'index'])->name('index');
        Route::get('/{id}', [ProductController::class, 'show'])->name('show');
    });

    // Shopping Bag
    Route::prefix('cart')->name('cart.')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('index');
        Route::post('/add', [CartController::class, 'add'])->name('add');
        Route::patch('/update/{id}', [CartController::class, 'update'])->name('update');
        Route::delete('/remove/{id}', [CartController::class, 'remove'])->name('remove');
    });

    // Checkout & Bakong KHQR
    Route::prefix('checkout')->name('checkout.')->middleware('auth')->group(function () {
        Route::get('/', [CheckoutController::class, 'index'])->name('index');
        Route::post('/', [CheckoutController::class, 'store'])->name('store');
    });

    // Orders & Verification
    Route::prefix('orders')->name('orders.')->middleware('auth')->group(function () {
        Route::get('/{id}', [OrderController::class, 'show'])->name('show');
        Route::get('/{id}/pending', [OrderController::class, 'pending'])->name('pending');
        Route::post('/{id}/slip', [OrderController::class, 'uploadSlip'])->name('upload-slip');
    });

    // Buyer Account & Profile
    Route::get('/dashboard', [BuyerDashboardController::class, 'index'])->middleware('auth')->name('buyer.dashboard');

    // Authentication Routes
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);
        Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
        Route::post('/register', [AuthController::class, 'register']);
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Admin Operations Portal Routes
    Route::prefix('admin')->name('admin.')->middleware('staff')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        // Order Fulfillment & Payment Verification
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{id}/review', [AdminOrderController::class, 'showReview'])->name('orders.review');
        Route::post('/orders/{id}/verify', [AdminOrderController::class, 'verifyPayment'])->name('orders.verify');
        Route::patch('/orders/{id}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');

        // Product Drops Inventory
        Route::resource('products', AdminProductController::class);

        // Users & Staff Access
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');

        // Payment Methods Configuration
        Route::get('/payment-methods', [AdminPaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::post('/payment-methods/{id}/toggle', [AdminPaymentMethodController::class, 'toggle'])->name('payment-methods.toggle');
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
