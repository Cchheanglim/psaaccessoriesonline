<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Display the viral Gen-Z accessories storefront.
     */
    public function index(Request $request)
    {
        $featured = Product::active()->take(8)->get();
        $bestsellers = Product::active()->where('badge', 'Bestseller')->take(4)->get();
        
        return view('home', compact('featured', 'bestsellers'));
    }
}
