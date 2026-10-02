<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::latest()->paginate(15);
        return view('admin.products', compact('products'));
    }

    public function create()
    {
        return view('admin.product-form', ['product' => new Product()]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'title_khmer' => 'nullable|string|max:200',
            'category' => 'required|string',
            'price_usd' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|string',
            'badge' => 'nullable|string',
            'material' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $validated['price_khr'] = round($validated['price_usd'] * 4100);
        $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(4);
        $validated['status'] = 'active';

        Product::create($validated);

        return redirect()->route('admin.products.index')->with('success', 'Product drop added!');
    }

    public function edit($id)
    {
        $product = Product::findOrFail($id);
        return view('admin.product-form', compact('product'));
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $validated = $request->validate([
            'title' => 'required|string|max:200',
            'title_khmer' => 'nullable|string|max:200',
            'category' => 'required|string',
            'price_usd' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|string',
            'badge' => 'nullable|string',
            'material' => 'nullable|string',
            'description' => 'nullable|string',
        ]);

        $validated['price_khr'] = round($validated['price_usd'] * 4100);
        $product->update($validated);

        return redirect()->route('admin.products.index')->with('success', 'Product updated!');
    }

    public function destroy($id)
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Only an Admin can delete products.');
        $product = Product::findOrFail($id);
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }
}
