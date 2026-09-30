<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display order receipt and delivery tracking timeline.
     */
    public function show($id)
    {
        $order = Order::with('items')->findOrFail($id);
        return view('orders.show', compact('order'));
    }

    /**
     * Display Bakong KHQR scan and payment screenshot upload screen.
     */
    public function pending($id)
    {
        $order = Order::with('items')->findOrFail($id);
        return view('orders.pending', compact('order'));
    }

    /**
     * Handle payment slip upload.
     */
    public function uploadSlip(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'payment_slip' => 'required|image|max:5120',
        ]);

        if ($request->hasFile('payment_slip')) {
            $path = $request->file('payment_slip')->store('slips', 'public');
            $order->update([
                'payment_slip_url' => '/storage/' . $path,
                'payment_status' => 'slip_uploaded',
            ]);
        }

        return redirect()->route('orders.show', $order->id)->with('success', 'Payment slip submitted! Admin is verifying.');
    }
}
