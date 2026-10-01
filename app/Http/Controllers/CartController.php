<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Display the shopping cart bag.
     */
    public function index(Request $request)
    {
        $cart = session()->get('cart', []);
        $deliveryFeeUsd = count($cart) > 0 ? 1.50 : 0.00;
        
        $subtotalUsd = 0;
        foreach ($cart as $item) {
            $subtotalUsd += $item['price_usd'] * $item['quantity'];
        }
        $totalUsd = $subtotalUsd + $deliveryFeeUsd;
        $totalKhr = round($totalUsd * 4100);

        return view('cart.index', compact('cart', 'subtotalUsd', 'deliveryFeeUsd', 'totalUsd', 'totalKhr'));
    }

    /**
     * Add an accessory to the cart.
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'nullable|integer|min:1|max:99',
        ]);

        $product = Product::active()->findOrFail($request->integer('product_id'));
        abort_if($product->stock < 1, 422, 'This product is out of stock.');
        // An empty quantity field arrives as null, which integer() turns into 0.
        $qty = max(1, $request->integer('quantity', 1));

        $cart = session()->get('cart', []);

        if (isset($cart[$product->id])) {
            $cart[$product->id]['quantity'] = min(99, $cart[$product->id]['quantity'] + $qty);
        } else {
            $cart[$product->id] = [
                'id' => $product->id,
                'title' => $product->title,
                'price_usd' => $product->price_usd,
                'price_khr' => $product->price_khr,
                'image' => $product->image,
                'quantity' => $qty,
            ];
        }

        session()->put('cart', $cart);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Added to bag.',
                'count' => array_sum(array_column($cart, 'quantity')),
                'item' => $cart[$product->id],
                'cart' => $cart,
            ]);
        }

        return redirect()->back()->with('success', 'Added to bag.');
    }

    /**
     * Update quantity of an item in the cart.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'quantity' => 'nullable|integer|min:0|max:99',
        ]);

        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            $qty = $request->integer('quantity', 1);
            if ($qty > 0) {
                $cart[$id]['quantity'] = $qty;
            } else {
                unset($cart[$id]);
            }
            session()->put('cart', $cart);
        }

        return response()->json(['success' => true, 'cart' => $cart]);
    }

    /**
     * Remove an item from the cart.
     */
    public function remove($id)
    {
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
        }

        return redirect()->route('cart.index')->with('success', 'Item removed');
    }
}
