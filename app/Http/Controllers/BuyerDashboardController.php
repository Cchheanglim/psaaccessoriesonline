<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BuyerDashboardController extends Controller
{
    /**
     * Display buyer account profile and recent accessory purchases.
     *
     * The route sits behind the "auth" middleware, so only the signed-in
     * user's own orders are ever listed.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $orders = $user->orders()->with('items')->latest()->get();

        return view('buyer.dashboard', compact('user', 'orders'));
    }
}
