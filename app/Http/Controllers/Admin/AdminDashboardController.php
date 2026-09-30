<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $totalRevenueUsd = Order::where('payment_status', 'verified')->sum('total_usd');
        $totalOrders = Order::count();
        $pendingPayments = Order::whereIn('payment_status', ['pending', 'slip_uploaded'])->count();
        $totalProducts = Product::count();
        $recentOrders = Order::with('items')->latest()->take(6)->get();

        return view('admin.dashboard', compact(
            'totalRevenueUsd',
            'totalOrders',
            'pendingPayments',
            'totalProducts',
            'recentOrders'
        ));
    }
}
