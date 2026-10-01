<?php

namespace App\Http\Controllers;

use App\Models\Product;

class HomeController extends Controller
{
    /**
     * Display the storefront home page.
     */
    public function index()
    {
        $featured = Product::active()->latest()->latest('id')->take(8)->get();
        $hero = $featured->first();

        return view('home', compact('featured', 'hero'));
    }
}
