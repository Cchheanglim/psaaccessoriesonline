<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:pending_payment,processing,out_for_delivery,delivered,cancelled',
            'payment_status' => 'nullable|in:pending,pending_slip,slip_uploaded,verified,failed',
        ]);

        $query = Order::with('items')->latest();

        if (! empty($validated['status'])) {
            $query->where('order_status', $validated['status']);
        }

        if (! empty($validated['payment_status'])) {
            $query->where('payment_status', $validated['payment_status']);
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('admin.orders', compact('orders'));
    }

    public function showReview(Order $order)
    {
        $order->load('items');

        return view('admin.slip-review', compact('order'));
    }

    public function verifyPayment(Order $order)
    {
        Gate::authorize('verifyPayment', $order);

        $order->update([
            'payment_status' => 'verified',
            'order_status' => 'processing',
            'paid_at' => now(),
        ]);

        return redirect()->route('admin.orders.index')->with('success', "Order #{$order->order_number} payment verified.");
    }

    /**
     * Mark an uploaded slip as not acceptable. The customer can then upload a
     * new one from their order page.
     */
    public function rejectPayment(Order $order)
    {
        Gate::authorize('verifyPayment', $order);

        $order->update(['payment_status' => 'failed']);

        return redirect()->route('admin.orders.index')->with('success', "Order #{$order->order_number}: slip rejected. The customer can upload a new one.");
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'order_status' => 'required|in:pending_payment,processing,out_for_delivery,delivered,cancelled',
        ]);

        Gate::authorize(
            $validated['order_status'] === 'cancelled' ? 'cancel' : 'updateStatus',
            $order
        );

        $order->update($validated);

        return back()->with('success', 'Order status updated.');
    }
}
