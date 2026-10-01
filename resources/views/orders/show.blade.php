@extends('layouts.app')

@section('title', 'Order ' . $order->order_number . ' | PsaOnlineAccessories')

@section('content')
@php
    $steps = [
        'pending_payment' => 'Order placed',
        'processing' => 'Packing',
        'out_for_delivery' => 'Out for delivery',
        'delivered' => 'Delivered',
    ];
    $isCancelled = $order->order_status === 'cancelled';
    $currentStep = array_search($order->order_status, array_keys($steps), true);
    $paymentLabels = [
        'pending' => 'Pay on delivery',
        'pending_slip' => 'Waiting for your transfer slip',
        'slip_uploaded' => 'Slip received, being checked',
        'verified' => 'Payment confirmed',
        'failed' => 'Payment could not be confirmed',
    ];
@endphp
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <!-- Header status card -->
    <div class="bg-white rounded-3xl border border-[#EFE4D6] p-6 shadow-xs mb-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <div class="text-xs text-[#FFA552] font-black">Order #{{ $order->order_number }}</div>
            <h1 class="text-2xl font-black text-[#2B1D1D] mt-0.5">Thank you for your order</h1>
            <p class="text-xs text-stone-500">{{ $paymentLabels[$order->payment_status] ?? ucwords(str_replace('_', ' ', $order->payment_status)) }}</p>
        </div>
        <div class="px-4 py-2 rounded-lg bg-[#F9F3EA] text-[#FFA552] border border-[#FFA552] text-xs font-black uppercase tracking-wider flex items-center gap-2">
            <span class="w-2 h-2 rounded-full {{ $isCancelled ? 'bg-red-600' : 'bg-emerald-600' }}"></span>
            <span>{{ ucwords(str_replace('_', ' ', $order->order_status)) }}</span>
        </div>
    </div>

    @if (in_array($order->payment_status, ['pending_slip', 'failed'], true) && ! $isCancelled)
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <span>{{ $order->payment_status === 'failed' ? 'We could not match your transfer slip to this order. Please upload a clearer one or message us on Telegram.' : 'We have not received your transfer slip yet.' }}</span>
            <a href="{{ route('orders.pending', $order) }}" class="inline-block px-4 py-2 rounded-lg bg-[#FFA552] text-white font-black">Pay and upload slip</a>
        </div>
    @endif

    <!-- Progress -->
    <div class="bg-white rounded-3xl border border-[#EFE4D6] p-6 shadow-xs mb-6">
        <h2 class="text-xs font-black text-[#2B1D1D] uppercase tracking-wider mb-4">Delivery progress</h2>
        @if ($isCancelled)
            <p class="text-xs font-bold text-red-700">This order was cancelled. Message us on Telegram if you have questions.</p>
        @else
            <ol class="grid grid-cols-4 gap-2 text-center text-xs font-bold">
                @foreach (array_values($steps) as $index => $label)
                    @php($done = $currentStep !== false && $index < $currentStep)
                    @php($current = $index === $currentStep)
                    <li class="space-y-1" @if ($current) aria-current="step" @endif>
                        <div class="w-8 h-8 rounded-full flex items-center justify-center mx-auto text-xs font-black
                            {{ $done ? 'bg-[#FFA552] text-white' : ($current ? 'bg-[#2B1D1D] text-[#FFA552] border border-[#FFA552]' : 'bg-[#F9F3EA] text-stone-400') }}">
                            @if ($done)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </div>
                        <div class="{{ $current ? 'text-[#FFA552] font-black' : ($done ? 'text-[#2B1D1D]' : 'text-stone-400') }}">{{ $label }}</div>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>

    <!-- Items -->
    <div class="bg-white rounded-3xl border border-[#EFE4D6] p-6 shadow-xs space-y-4">
        <h2 class="text-base font-extrabold text-[#2B1D1D] border-b border-[#F9F3EA] pb-3">Items in this order</h2>
        <div class="divide-y divide-[#F9F3EA]">
            @foreach($order->items as $item)
            <div class="py-3 flex items-center justify-between text-xs">
                <div class="flex items-center gap-3">
                    <img src="{{ $item->product_image }}" alt="{{ $item->product_title }}" class="w-12 h-12 object-cover rounded-xl border border-[#EFE4D6]" />
                    <div>
                        <div class="font-extrabold text-[#2B1D1D]">{{ $item->product_title }}</div>
                        <div class="text-stone-500">Qty: {{ $item->quantity }} &bull; ${{ number_format($item->price_usd, 2) }} each</div>
                    </div>
                </div>
                <div class="font-black text-[#2B1D1D]">${{ number_format($item->total_usd, 2) }}</div>
            </div>
            @endforeach
        </div>

        <div class="border-t border-[#F9F3EA] pt-4 space-y-1.5 text-xs text-right">
            <div class="flex justify-between text-stone-600">
                <span>Subtotal:</span>
                <span class="font-bold">${{ number_format($order->subtotal_usd, 2) }}</span>
            </div>
            <div class="flex justify-between text-stone-600">
                <span>Delivery:</span>
                <span class="font-bold">${{ number_format($order->delivery_fee_usd, 2) }}</span>
            </div>
            <div class="flex justify-between text-sm font-black text-[#2B1D1D] pt-2 border-t border-[#F9F3EA]">
                <span>{{ $order->payment_status === 'verified' ? 'Total paid:' : 'Total:' }}</span>
                <span>${{ number_format($order->total_usd, 2) }} ({{ number_format($order->total_khr) }} ៛)</span>
            </div>
        </div>
    </div>
</div>
@endsection
