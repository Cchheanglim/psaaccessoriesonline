@extends('layouts.admin')

@section('title', 'Order ' . $order->order_number . ' | PsaOnlineAccessories Admin')

@section('content')
@php
    $statuses = [
        'pending_payment' => 'Pending payment',
        'processing' => 'Packing',
        'out_for_delivery' => 'Out for delivery',
        'delivered' => 'Delivered',
    ];
    if (auth()->user()->isAdmin()) {
        $statuses['cancelled'] = 'Cancelled';
    }
    $canReviewSlip = $order->payment_status === 'slip_uploaded';
@endphp
<div class="max-w-4xl mx-auto space-y-6">
    <div>
        <a href="{{ route('admin.orders.index') }}" class="text-xs text-stone-500 hover:text-[#2B1D1D] font-bold">&larr; Back to orders</a>
        <h1 class="text-2xl font-black text-[#2B1D1D] mt-1">Order #{{ $order->order_number }}</h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
        <!-- Uploaded Slip -->
        <div class="bg-[#0B2B26] p-6 rounded-2xl border border-[#235347] space-y-4 text-center">
            <h2 class="text-xs font-bold text-[#8EB69B] uppercase tracking-wider">Customer transfer slip</h2>
            @if ($order->payment_slip_url)
                <a href="{{ route('orders.slip', $order) }}" target="_blank" rel="noopener" class="block bg-white p-2 rounded-xl border border-slate-300 max-w-sm mx-auto shadow-inner">
                    <img src="{{ route('orders.slip', $order) }}" alt="Transfer slip for order {{ $order->order_number }}" class="w-full max-h-[28rem] object-contain rounded-lg" />
                </a>
                <p class="text-[11px] text-[#8EB69B]">Opens full size in a new tab.</p>
            @else
                <p class="py-16 text-xs text-[#8EB69B]">
                    {{ $order->payment_method === 'cod' ? 'Cash on delivery. No slip needed.' : 'No slip uploaded yet.' }}
                </p>
            @endif
        </div>

        <!-- Details & Actions -->
        <div class="bg-[#0B2B26] p-6 rounded-2xl border border-[#235347] space-y-5">
            <h2 class="text-base font-extrabold text-white border-b border-[#235347] pb-3">Order details</h2>

            <dl class="space-y-2 text-xs">
                <div class="flex justify-between gap-4"><dt class="text-[#8EB69B]">Customer</dt><dd class="font-bold text-white text-right">{{ $order->customer_name }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-[#8EB69B]">Phone / Telegram</dt><dd class="font-bold text-white text-right">{{ $order->customer_phone }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-[#8EB69B]">Delivery address</dt><dd class="text-white text-right">{{ $order->delivery_address }}</dd></div>
                @if ($order->delivery_notes)
                <div class="flex justify-between gap-4"><dt class="text-[#8EB69B]">Notes</dt><dd class="text-white text-right">{{ $order->delivery_notes }}</dd></div>
                @endif
                <div class="flex justify-between gap-4"><dt class="text-[#8EB69B]">Method</dt><dd class="text-white text-right">{{ ucwords(str_replace('_', ' ', $order->payment_method)) }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-[#8EB69B]">Payment</dt><dd class="text-right">@include('admin.partials.payment-badge', ['status' => $order->payment_status])</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-[#8EB69B]">Amount expected</dt><dd class="font-black text-emerald-400 text-sm text-right">${{ number_format($order->total_usd, 2) }} ({{ number_format($order->total_khr) }} ៛)</dd></div>
            </dl>

            <div class="border-t border-[#235347] pt-4">
                <h3 class="text-[11px] font-bold text-[#8EB69B] uppercase tracking-wider mb-2">Items</h3>
                <ul class="space-y-1 text-xs text-white">
                    @foreach ($order->items as $item)
                        <li class="flex justify-between gap-4"><span>{{ $item->quantity }} &times; {{ $item->product_title }}</span><span>${{ number_format($item->total_usd, 2) }}</span></li>
                    @endforeach
                </ul>
            </div>

            @if ($canReviewSlip)
            <div class="pt-4 border-t border-[#235347] space-y-3">
                <form action="{{ route('admin.orders.verify', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs shadow-md transition-all">
                        Approve payment
                    </button>
                </form>
                <form action="{{ route('admin.orders.reject', $order) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2.5 rounded-xl border border-red-500/50 text-red-400 hover:bg-red-500/10 text-xs font-bold transition">
                        Reject slip (wrong amount or unreadable)
                    </button>
                </form>
            </div>
            @endif

            @can('updateStatus', $order)
            <form action="{{ route('admin.orders.status', $order) }}" method="POST" class="pt-4 border-t border-[#235347] space-y-2">
                @csrf
                @method('PATCH')
                <label for="order_status" class="block text-[11px] font-bold text-[#8EB69B] uppercase tracking-wider">Delivery status</label>
                <div class="flex gap-2">
                    <select id="order_status" name="order_status" class="flex-1 rounded-lg bg-[#051F20] border border-[#235347] text-white text-xs px-3 py-2">
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected($order->order_status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-4 py-2 rounded-lg bg-[#235347] hover:bg-[#8EB69B] hover:text-[#051F20] text-white font-bold text-xs transition">Update</button>
                </div>
            </form>
            @else
            <p class="pt-4 border-t border-[#235347] text-xs text-[#8EB69B]">This order was cancelled by an admin. Only an admin can change it.</p>
            @endcan
        </div>
    </div>
</div>
@endsection
