@extends('layouts.app')

@section('title', ($product->title ?? 'Product Details') . ' — PsaOnline')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <!-- Breadcrumb -->
    <div class="mb-6 text-xs text-[#235347] font-bold flex items-center gap-1.5">
        <a href="{{ route('home') }}" class="hover:underline">Home</a>
        <span>/</span>
        <a href="{{ route('products.index') }}" class="hover:underline">Catalog</a>
        <span>/</span>
        <span class="text-slate-500">{{ $product->title }}</span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-start">
        <!-- Pure White Image Showcase Frame with Sage Borders -->
        <div class="lg:col-span-6 bg-white rounded-3xl border-2 border-[#8EB69B] p-4 sm:p-6 shadow-sm">
            <div class="aspect-square rounded-2xl bg-white border border-[#E2ECE5] overflow-hidden flex items-center justify-center p-2">
                <img
                    src="{{ $product->image }}"
                    alt="{{ $product->title }}"
                    class="w-full h-full object-cover rounded-xl"
                />
            </div>
        </div>

        <!-- Product Details & Actions -->
        <div class="lg:col-span-6 space-y-6">
            <div>
                <div class="flex items-center gap-2 text-xs font-bold text-[#235347]">
                    <span class="px-2.5 py-0.5 rounded-full bg-[#DAF1DE] uppercase">{{ $product->category_label ?? $product->category }}</span>
                    <span>★ {{ $product->rating }} ({{ $product->review_count }} reviews)</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-[#051F20] mt-2 tracking-tight">{{ $product->title }}</h1>
                @if($product->title_khmer)
                <p class="text-sm text-slate-500 khmer-font mt-1">{{ $product->title_khmer }}</p>
                @endif
            </div>

            <!-- Price -->
            <div class="p-4 rounded-2xl bg-white border border-[#E2ECE5] flex items-baseline justify-between">
                <div>
                    <div class="text-2xl sm:text-3xl font-black text-[#051F20]">${{ number_format($product->price_usd, 2) }}</div>
                    <div class="text-xs text-[#235347] font-bold">{{ number_format($product->price_khr) }} ៛ (Bakong KHQR)</div>
                </div>
                <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold">In Stock &bull; Phnom Penh Delivery</span>
            </div>

            <!-- Description -->
            <div class="space-y-2 text-xs text-slate-700 leading-relaxed font-medium">
                <p>{{ $product->description }}</p>
                <div class="grid grid-cols-2 gap-2 pt-2 text-[11px]">
                    <div><span class="text-slate-400">Material:</span> <span class="font-bold text-[#051F20]">{{ $product->material ?? 'Premium Alloy / Stainless Steel' }}</span></div>
                    <div><span class="text-slate-400">Color:</span> <span class="font-bold text-[#051F20]">{{ $product->color ?? 'Chrome Silver' }}</span></div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3 pt-2">
                <button
                    onclick="addToCart('{{ $product->id }}', 1)"
                    class="btn-press w-full py-4 rounded-2xl bg-[#051F20] hover:bg-[#0B2B26] text-white font-extrabold text-sm shadow-md flex items-center justify-center gap-2 cursor-pointer transition-all"
                >
                    <svg class="w-4 h-4 text-[#8EB69B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    <span>Add to Shopping Bag &bull; ${{ number_format($product->price_usd, 2) }}</span>
                </button>

                <a
                    href="{{ route('checkout.index') }}"
                    class="btn-press w-full py-3.5 rounded-2xl bg-white hover:bg-[#DAF1DE] text-[#051F20] font-bold text-xs border-2 border-[#8EB69B]/50 transition-all text-center block"
                >
                    Buy Now with Bakong KHQR &rarr;
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
