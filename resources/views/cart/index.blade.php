@extends('layouts.app')

@section('title', 'Your Bag | PsaOnlineAccessories')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <div class="mb-6">
        <h1 class="text-2xl sm:text-3xl font-black text-[#2B1D1D] tracking-tight">Your bag</h1>
        <p class="text-xs sm:text-sm text-stone-500">Delivery anywhere in Phnom Penh is a flat ${{ number_format(1.50, 2) }}.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- Cart Items -->
        <div class="lg:col-span-8 bg-white rounded-3xl border border-[#EFE4D6] p-5 sm:p-6">
            <div id="cartItemsList" class="divide-y divide-[#F9F3EA]">
                @forelse($cart as $id => $item)
                <div class="py-4 flex gap-4 items-center">
                    <img src="{{ $item['image'] ?? '' }}" alt="{{ $item['title'] }}" class="w-16 h-16 sm:w-20 sm:h-20 object-cover rounded-2xl bg-white border border-[#EFE4D6] p-1" />
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-extrabold text-[#2B1D1D] truncate">{{ $item['title'] }}</h3>
                        <div class="text-xs text-[#FFA552] font-black mt-1">${{ number_format($item['price_usd'], 2) }}</div>
                        <button type="button" onclick="updateQty('{{ $id }}', 0)" class="mt-1 text-[11px] font-bold text-stone-500 hover:text-red-600 underline">Remove</button>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="updateQty('{{ $id }}', {{ $item['quantity'] - 1 }})" aria-label="Decrease quantity" class="w-8 h-8 rounded-lg border border-[#EFE4D6] bg-[#FDFBF7] flex items-center justify-center font-bold text-xs">-</button>
                        <span class="text-xs font-bold w-6 text-center text-[#2B1D1D]">{{ $item['quantity'] }}</span>
                        <button type="button" onclick="updateQty('{{ $id }}', {{ $item['quantity'] + 1 }})" aria-label="Increase quantity" class="w-8 h-8 rounded-lg border border-[#EFE4D6] bg-[#FDFBF7] flex items-center justify-center font-bold text-xs">+</button>
                    </div>
                    <div class="text-right">
                        <div class="text-xs font-black text-[#2B1D1D]">${{ number_format($item['price_usd'] * $item['quantity'], 2) }}</div>
                    </div>
                </div>
                @empty
                <div id="emptyCartState" class="py-12 text-center space-y-4">
                    <div class="w-16 h-16 rounded-full bg-[#F9F3EA] text-[#FFA552] border border-[#FFA552] flex items-center justify-center mx-auto">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    </div>
                    <h3 class="text-base font-bold text-[#2B1D1D]">Your bag is empty</h3>
                    <p class="text-xs text-stone-500">Items you add from the catalog will appear here.</p>
                    <a href="{{ route('products.index') }}" class="inline-block px-6 py-2.5 rounded-lg bg-[#FFA552] text-white font-black text-xs">Browse products</a>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Order Summary -->
        <div class="lg:col-span-4 bg-white rounded-3xl border-2 border-[#EFE4D6] p-6 shadow-md space-y-4">
            <h2 class="text-base font-extrabold text-[#2B1D1D] border-b border-[#F9F3EA] pb-3">Summary</h2>

            <div class="space-y-2 text-xs">
                <div class="flex justify-between text-stone-600">
                    <span>Subtotal</span>
                    <span id="cartSubtotalText" class="font-bold text-[#2B1D1D]">${{ number_format($subtotalUsd, 2) }}</span>
                </div>
                <div class="flex justify-between text-stone-600">
                    <span>Delivery in Phnom Penh</span>
                    <span id="cartDeliveryText" class="font-bold text-[#FFA552]">${{ number_format($deliveryFeeUsd, 2) }}</span>
                </div>
                <div class="border-t border-[#F9F3EA] pt-2 flex justify-between text-sm font-black text-[#2B1D1D]">
                    <span>Total (USD)</span>
                    <span id="cartTotalText">${{ number_format($totalUsd, 2) }}</span>
                </div>
                <div class="flex justify-between text-[11px] text-[#FFA552] font-black">
                    <span>Total in riel</span>
                    <span id="cartKhrText">{{ number_format($totalKhr) }} ៛</span>
                </div>
            </div>

            @if (count($cart) > 0)
            <div class="pt-2">
                <a href="{{ route('checkout.index') }}" class="btn-press w-full py-3.5 rounded-2xl bg-[#FFA552] hover:bg-[#E88C35] text-white font-black text-xs shadow-md flex items-center justify-center gap-2 transition-all">
                    <span>Continue to checkout</span>
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </a>
            </div>
            @endif

            <p class="text-[11px] text-center text-stone-500 pt-2">
                Pay by Bakong KHQR, ABA Pay or cash on delivery.
            </p>
        </div>
    </div>
</div>
@endsection
