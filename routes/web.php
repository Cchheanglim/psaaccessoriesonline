<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPaymentMethodController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuyerDashboardController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Storefront
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/home', [HomeController::class, 'index']);

Route::prefix('products')->name('products.')->group(function () {
    Route::get('/', [ProductController::class, 'index'])->name('index');
    Route::get('/{id}', [ProductController::class, 'show'])->name('show')->whereNumber('id');
});

// Legal
Route::view('/privacy', 'legal.privacy')->name('privacy');
Route::view('/terms', 'legal.terms')->name('terms');

// Cart
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::patch('/update/{id}', [CartController::class, 'update'])->name('update')->whereNumber('id');
    Route::delete('/remove/{id}', [CartController::class, 'remove'])->name('remove')->whereNumber('id');
});

// Checkout. Open to guests; ownership of the resulting order is tracked in the
// session (see OrderPolicy).
Route::prefix('checkout')->name('checkout.')->group(function () {
    Route::get('/', [CheckoutController::class, 'index'])->name('index');
    Route::post('/', [CheckoutController::class, 'store'])->name('store')->middleware('throttle:checkout');
});

// Orders. Every action is authorised per order in OrderController.
Route::prefix('orders')->name('orders.')->whereNumber('order')->group(function () {
    Route::get('/{order}', [OrderController::class, 'show'])->name('show');
    Route::get('/{order}/pending', [OrderController::class, 'pending'])->name('pending');
    Route::post('/{order}/slip', [OrderController::class, 'uploadSlip'])->name('upload-slip')->middleware('throttle:uploads');
    Route::get('/{order}/slip', [OrderController::class, 'showSlip'])->name('slip');
});

// Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [BuyerDashboardController::class, 'index'])->name('buyer.dashboard');
});

// Admin portal. Staff and admins may operate orders and stock; account
// management, payment gateways and deletions are admin-only.
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:staff,admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    Route::whereNumber('order')->group(function () {
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}/review', [AdminOrderController::class, 'showReview'])->name('orders.review');
        Route::post('/orders/{order}/verify', [AdminOrderController::class, 'verifyPayment'])->name('orders.verify');
        Route::post('/orders/{order}/reject', [AdminOrderController::class, 'rejectPayment'])->name('orders.reject');
        Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    });

    Route::resource('products', AdminProductController::class)->except(['show', 'destroy']);

    Route::middleware('role:admin')->group(function () {
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');

        Route::get('/payment-methods', [AdminPaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::post('/payment-methods/{id}/toggle', [AdminPaymentMethodController::class, 'toggle'])->name('payment-methods.toggle')->whereNumber('id');
    });
});
