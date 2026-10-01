<?php

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Every route here is prefixed with /api and rate limited by the "api"
| limiter (see AppServiceProvider). Only public, read-only catalogue data is
| exposed. Order data is deliberately not available here: the api group has
| no session, so there is no way to prove a caller owns an order.
|
*/

Route::get('/products', function (Request $request) {
    $request->validate([
        'category' => 'nullable|string|in:all,jewelry,eyewear,bags,hair,charms',
    ]);

    $query = Product::active();

    $category = $request->query('category');
    if ($category && $category !== 'all') {
        $query->where('category', $category);
    }

    return response()->json($query->get([
        'id', 'title', 'title_khmer', 'slug', 'category', 'category_label',
        'price_usd', 'price_khr', 'stock', 'image', 'badge', 'material',
        'color', 'description',
    ]));
});
