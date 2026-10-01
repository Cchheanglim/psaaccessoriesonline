<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $totalRevenueUsd = Order::where('payment_status', 'verified')->sum('total_usd');
        $totalOrders = Order::count();
        $slipsToReview = Order::where('payment_status', 'slip_uploaded')->count();
        $activeProducts = Product::active()->count();
        $recentOrders = Order::latest()->latest('id')->take(6)->get();

        return view('admin.dashboard', compact(
            'totalRevenueUsd',
            'totalOrders',
            'slipsToReview',
            'activeProducts',
            'recentOrders'
        ));
    }
}
