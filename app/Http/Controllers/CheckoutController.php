<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * Display the checkout page with interactive map and payment options.
     */
    public function index(Request $request)
    {
        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('products.index')->with('warning', 'Your bag is empty.');
        }

        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        $subtotalUsd = array_reduce($cart, fn($acc, $item) => $acc + ($item['price_usd'] * $item['quantity']), 0);
        $deliveryFeeUsd = 1.50;
        $totalUsd = $subtotalUsd + $deliveryFeeUsd;
        $totalKhr = round($totalUsd * 4100);

        return view('checkout.index', compact('cart', 'paymentMethods', 'subtotalUsd', 'deliveryFeeUsd', 'totalUsd', 'totalKhr'));
    }

    /**
     * Process order submission and redirect to pending KHQR verification or receipt.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:120',
            'customer_phone' => 'required|string|max:30',
            'delivery_address' => 'required|string|max:300',
            'delivery_notes' => 'nullable|string|max:200',
            'payment_method' => 'required|in:bakong_khqr,aba_pay,acleda_khqr,visa_card,cod',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $cart = session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('products.index');
        }

        $subtotalUsd = array_reduce($cart, fn($acc, $item) => $acc + ($item['price_usd'] * $item['quantity']), 0);
        $deliveryFeeUsd = 1.50;
        $totalUsd = $subtotalUsd + $deliveryFeeUsd;
        $totalKhr = round($totalUsd * 4100);

        $orderNumber = 'PSA-' . strtoupper(Str::random(6));

        $order = Order::create([
            'order_number' => $orderNumber,
            'user_id' => $request->user()?->getAuthIdentifier(),
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'],
            'delivery_address' => $validated['delivery_address'],
            'delivery_notes' => $validated['delivery_notes'] ?? null,
            'latitude' => $validated['latitude'] ?? 11.5564,
            'longitude' => $validated['longitude'] ?? 104.9282,
            'subtotal_usd' => $subtotalUsd,
            'subtotal_khr' => round($subtotalUsd * 4100),
            'delivery_fee_usd' => $deliveryFeeUsd,
            'delivery_fee_khr' => round($deliveryFeeUsd * 4100),
            'total_usd' => $totalUsd,
            'total_khr' => $totalKhr,
            'payment_method' => $validated['payment_method'],
            'payment_status' => $validated['payment_method'] === 'cod' ? 'pending' : 'pending_slip',
            'order_status' => 'pending_payment',
        ]);

        foreach ($cart as $item) {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item['id'] ?? null,
                'product_title' => $item['title'],
                'product_image' => $item['image'] ?? '',
                'price_usd' => $item['price_usd'],
                'price_khr' => $item['price_khr'] ?? round($item['price_usd'] * 4100),
                'quantity' => $item['quantity'],
                'total_usd' => $item['price_usd'] * $item['quantity'],
                'total_khr' => round($item['price_usd'] * $item['quantity'] * 4100),
            ]);
        }

        // Clear bag session
        session()->forget('cart');

        if (in_array($validated['payment_method'], ['bakong_khqr', 'aba_pay', 'acleda_khqr'], true)) {
            return redirect()->route('orders.pending', $order->id);
        }

        return redirect()->route('orders.show', $order->id);
    }
}
