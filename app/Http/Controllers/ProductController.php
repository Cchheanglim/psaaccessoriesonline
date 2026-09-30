<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of products with filtering, search, and sorting.
     */
    public function index(Request $request)
    {
        $query = Product::active();

        // Filter by category
        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Search by keyword
        if ($request->filled('q')) {
            $keyword = $request->q;
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                  ->orWhere('title_khmer', 'like', "%{$keyword}%")
                  ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        // Price range filter
        if ($request->filled('min_price')) {
            $query->where('price_usd', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price_usd', '<=', (float) $request->max_price);
        }

        // Sorting
        $sort = $request->get('sort', 'trending');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price_usd', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price_usd', 'desc');
                break;
            case 'newest':
                $query->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('rating', 'desc');
        }

        $products = $query->paginate(12);

        return view('products.index', compact('products'));
    }

    /**
     * Display a specific product details page.
     */
    public function show($id)
    {
        $product = Product::findOrFail($id);
        $related = Product::active()
            ->where('category', $product->category)
            ->where('id', '!=', $product->id)
            ->take(4)
            ->get();

        return view('products.show', compact('product', 'related'));
    }
}
