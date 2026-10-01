<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    /**
     * Display order receipt and delivery tracking timeline.
     */
    public function show(Order $order)
    {
        Gate::authorize('view', $order);

        $order->load('items');

        return view('orders.show', compact('order'));
    }

    /**
     * Display Bakong KHQR scan and payment screenshot upload screen.
     */
    public function pending(Order $order)
    {
        Gate::authorize('view', $order);

        $order->load('items');

        return view('orders.pending', compact('order'));
    }

    /**
     * Handle payment slip upload.
     *
     * Slips are bank transfer screenshots containing account numbers, so they
     * are written to the private "local" disk and only served back through
     * showSlip() after an authorisation check.
     */
    public function uploadSlip(Request $request, Order $order)
    {
        Gate::authorize('uploadSlip', $order);

        $request->validate([
            'payment_slip' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $path = $request->file('payment_slip')->store('slips', 'local');

        if ($order->payment_slip_url) {
            Storage::disk('local')->delete($order->payment_slip_url);
        }

        $order->update([
            'payment_slip_url' => $path,
            'payment_status' => 'slip_uploaded',
        ]);

        return redirect()->route('orders.show', $order)->with('success', 'Payment slip submitted. We will verify it shortly.');
    }

    /**
     * Stream a stored payment slip to someone allowed to see the order.
     */
    public function showSlip(Order $order)
    {
        Gate::authorize('view', $order);

        $path = $order->payment_slip_url;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
