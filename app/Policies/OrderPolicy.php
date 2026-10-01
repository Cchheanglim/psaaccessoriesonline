<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

/**
 * Who may see or act on an order.
 *
 * Checkout is open to guests, so an order is not always tied to a user. For
 * those, the only proof of ownership is that this browser session is the one
 * that placed it: CheckoutController records the new order id in the session
 * and that list is consulted here. Without this, changing the id in
 * /orders/5 would expose another customer's name, phone and address.
 */
class OrderPolicy
{
    public function view(?User $user, Order $order): bool
    {
        if ($user?->isStaff()) {
            return true;
        }

        if ($user && $order->user_id !== null && (int) $order->user_id === (int) $user->id) {
            return true;
        }

        return $this->placedInThisSession($order);
    }

    /**
     * Uploading a payment slip is allowed for whoever may view the order, but
     * only while payment has not already been verified.
     */
    public function uploadSlip(?User $user, Order $order): bool
    {
        return $this->view($user, $order) && $order->payment_status !== 'verified';
    }

    public function verifyPayment(User $user, Order $order): bool
    {
        return $user->isStaff();
    }

    /**
     * Staff may move an order along, but only an admin may change an order
     * that an admin has cancelled; otherwise staff could reverse a cancel.
     */
    public function updateStatus(User $user, Order $order): bool
    {
        if ($order->order_status === 'cancelled') {
            return $user->isAdmin();
        }

        return $user->isStaff();
    }

    /**
     * Cancelling an order is a financial action, so staff are excluded.
     */
    public function cancel(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    protected function placedInThisSession(Order $order): bool
    {
        $request = request();

        if (! $request->hasSession()) {
            return false;
        }

        return in_array(
            (int) $order->id,
            array_map('intval', (array) $request->session()->get('placed_order_ids', [])),
            true
        );
    }
}
