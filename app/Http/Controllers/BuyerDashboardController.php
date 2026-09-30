<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BuyerDashboardController extends Controller
{
    /**
     * Display buyer account profile and recent accessory purchases.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $orders = $user 
            ? Order::where('user_id', $user->id)->with('items')->latest()->get()
            : Order::with('items')->latest()->take(5)->get();

        return view('buyer.dashboard', compact('user', 'orders'));
    }
}
