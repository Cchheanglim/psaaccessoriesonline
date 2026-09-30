<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('items')->latest();

        if ($request->filled('status')) {
            $query->where('order_status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $orders = $query->paginate(15);

        return view('admin.orders', compact('orders'));
    }

    public function showReview($id)
    {
        $order = Order::with('items')->findOrFail($id);
        return view('admin.slip-review', compact('order'));
    }

    public function verifyPayment(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $order->update([
            'payment_status' => 'verified',
            'order_status' => 'processing',
            'paid_at' => now(),
        ]);

        return redirect()->route('admin.orders.index')->with('success', "Order #{$order->order_number} payment verified successfully!");
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        $validated = $request->validate([
            'order_status' => 'required|in:pending_payment,processing,out_for_delivery,delivered,cancelled',
        ]);

        $order->update($validated);

        return back()->with('success', 'Order status updated.');
    }
}
