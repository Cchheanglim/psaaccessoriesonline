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
        $validated = $request->validate($this->rules());

        $validated['price_khr'] = round($validated['price_usd'] * 4100);
        $validated['slug'] = Str::slug($validated['title']) . '-' . Str::lower(Str::random(4));
        $validated['status'] = 'active';

        Product::create($validated);

        return redirect()->route('admin.products.index')->with('success', 'Product added.');
    }

    public function edit(Product $product)
    {
        return view('admin.product-form', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate($this->rules());

        $validated['price_khr'] = round($validated['price_usd'] * 4100);
        $product->update($validated);

        return redirect()->route('admin.products.index')->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted.');
    }

    /**
     * Shared validation for create and update. "image" is rendered into an
     * <img src>, so it is limited to http(s) URLs to rule out javascript: and
     * data: payloads.
     */
    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'title_khmer' => 'nullable|string|max:200',
            'category' => 'required|string|in:jewelry,eyewear,bags,hair,charms',
            'price_usd' => 'required|numeric|min:0|max:100000',
            'stock' => 'required|integer|min:0|max:1000000',
            'image' => 'nullable|url:http,https|max:2048',
            'badge' => 'nullable|string|max:50',
            'material' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:5000',
        ];
    }
}
