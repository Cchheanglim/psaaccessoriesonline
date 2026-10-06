<?php

namespace App\Support;

use App\Models\Order;
use App\Models\User;
use App\Models\UserNotification;

/**
 * Creates bell notifications. Customer-side notices go to the person who placed the order;
 * staff-side notices go to the staff member handling the order, or to every active staff
 * member and admin while nobody has picked it up yet.
 */
class Notifier
{
    public static function toCustomer(Order $order, string $type, string $title, ?string $body = null, ?string $link = null): void
    {
        $customerUserId = $order->loadMissing('customer')->user_id;
        if (! $customerUserId) {
            return;
        }

        UserNotification::create([
            'user_id' => $customerUserId,
            'order_id' => $order->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'link' => $link ?? 'order-detail.html?order='.rawurlencode($order->order_number),
        ]);
    }

    public static function toStaff(Order $order, string $type, string $title, ?string $body = null, ?int $exceptUserId = null): void
    {
        $recipients = $order->handled_by
            ? [$order->handled_by]
            : User::whereHas('roleRecord', fn ($q) => $q->whereIn('name', ['staff', 'admin']))
                ->where(fn ($q) => $q->whereNull('status')->orWhere('status', 'Active'))->pluck('id')->all();

        $link = 'order-detail--admin-payment-submitted.html?order='.rawurlencode($order->order_number);
        $now = now();
        $rows = [];
        $customerUserId = $order->loadMissing('customer')->user_id;
        foreach (array_unique($recipients) as $userId) {
            if ($userId === $exceptUserId || $userId === $customerUserId) {
                continue;
            }
            $rows[] = [
                'user_id' => $userId,
                'order_id' => $order->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'link' => $type === 'message' ? $link.'#messages' : $link,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows) {
            UserNotification::insert($rows);
        }
    }
}
