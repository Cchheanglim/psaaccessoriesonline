@extends('layouts.app')

@section('title', 'Order Receipt & Tracking — PsaOnline')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <!-- Header status card in Cotton Beige -->
    <div class="bg-white rounded-3xl border border-[#EFE4D6] p-6 shadow-sm mb-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <div class="text-xs text-[#FFA552] font-black">Order #{{ $order->order_number }}</div>
            <h1 class="text-2xl font-black text-[#2B1D1D] mt-0.5">Order Confirmation</h1>
            <p class="text-xs text-stone-500">Thank you for supporting our indie accessories studio!</p>
        </div>
        <div class="px-4 py-2 rounded-full bg-[#F9F3EA] text-[#FFA552] border border-[#FFA552] text-xs font-black uppercase tracking-wider flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
            <span>{{ ucwords(str_replace('_', ' ', $order->order_status)) }}</span>
        </div>
    </div>

    <!-- Stepper Tracker -->
    <div class="bg-white rounded-3xl border border-[#EFE4D6] p-6 shadow-sm mb-6">
        <h2 class="text-xs font-black text-[#2B1D1D] uppercase tracking-wider mb-4">Courier Progress</h2>
        <div class="grid grid-cols-4 gap-2 text-center text-xs font-bold">
            <div class="space-y-1">
                <div class="w-8 h-8 rounded-full bg-[#FFA552] text-white flex items-center justify-center mx-auto text-xs font-black">✓</div>
                <div class="text-[#2B1D1D]">Confirmed</div>
            </div>
            <div class="space-y-1">
                <div class="w-8 h-8 rounded-full bg-[#FFA552] text-white flex items-center justify-center mx-auto text-xs font-black">✓</div>
                <div class="text-[#2B1D1D]">Packed</div>
            </div>
            <div class="space-y-1">
                <div class="w-8 h-8 rounded-full bg-[#2B1D1D] text-[#FFA552] border border-[#FFA552] flex items-center justify-center mx-auto text-xs animate-pulse">🛵</div>
                <div class="text-[#FFA552] font-black">On The Way</div>
            </div>
            <div class="space-y-1">
                <div class="w-8 h-8 rounded-full bg-[#F9F3EA] text-stone-400 flex items-center justify-center mx-auto text-xs">4</div>
                <div class="text-stone-400">Delivered</div>
            </div>
        </div>
    </div>

    <!-- Itemized List in Pure White with Cotton borders -->
    <div class="bg-white rounded-3xl border border-[#EFE4D6] p-6 shadow-sm space-y-4">
        <h2 class="text-base font-extrabold text-[#2B1D1D] border-b border-[#F9F3EA] pb-3">Accessories in this Order</h2>
        <div class="divide-y divide-[#F9F3EA]">
            @foreach($order->items as $item)
            <div class="py-3 flex items-center justify-between text-xs">
                <div class="flex items-center gap-3">
                    <img src="{{ $item->product_image }}" class="w-12 h-12 object-cover rounded-xl border border-[#EFE4D6]" />
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
                <span>Total Paid:</span>
                <span>${{ number_format($order->total_usd, 2) }} ({{ number_format($order->total_khr) }} ៛)</span>
            </div>
        </div>
    </div>
</div>
@endsection
