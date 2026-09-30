<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Product;
use App\Models\Order;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Products API
Route::get('/products', function (Request $request) {
    $category = $request->query('category');
    $query = Product::active();
    if ($category && $category !== 'all') {
        $query->where('category', $category);
    }
    return response()->json($query->get());
});

// KHQR Verification Status
Route::get('/orders/{orderNumber}/status', function ($orderNumber) {
    $order = Order::where('order_number', $orderNumber)->firstOrFail();
    return response()->json([
        'order_number' => $order->order_number,
        'payment_status' => $order->payment_status,
        'order_status' => $order->order_status,
    ]);
});
