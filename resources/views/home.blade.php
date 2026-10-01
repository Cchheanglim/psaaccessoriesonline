@extends('layouts.app')

@section('title', 'PsaOnlineAccessories | Jewelry, Eyewear and Bags in Phnom Penh')

@section('content')
@php
    $homeCategories = [
        'jewelry' => 'Jewelry',
        'eyewear' => 'Sunglasses',
        'bags' => 'Bags',
        'hair' => 'Clips and pins',
        'charms' => 'Phone charms',
    ];
@endphp

<!-- Hero Section -->
<section class="relative overflow-hidden py-10 sm:py-16 lg:py-20 bg-[#FDFBF7]">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white border border-[#EFE4D6] text-xs font-extrabold text-[#2B1D1D]">
                    <span>Phnom Penh</span>
                    <span class="text-[#FFA552]">&bull;</span>
                    <span class="text-[#FFA552] font-black">Delivered for a flat $1.50</span>
                </div>

                <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-[#2B1D1D] tracking-tight leading-[1.1]">
                    Jewelry, sunglasses and bags,
                    <span class="text-[#FFA552]">delivered across Phnom Penh.</span>
                </h1>

                <p class="text-sm sm:text-base text-[#4A3333] max-w-xl mx-auto lg:mx-0 leading-relaxed font-medium">
                    Stainless steel rings and chains, tinted sunglasses, nylon bags, claw clips and phone charms.
                    Every price is listed in US dollars and riel, and you can pay by Bakong KHQR, ABA Pay or cash on delivery.
                </p>

                <div class="flex flex-wrap items-center justify-center lg:justify-start gap-3.5 pt-2">
                    <a href="{{ route('products.index') }}" class="btn-press px-6 py-3.5 rounded-2xl bg-[#FFA552] hover:bg-[#E88C35] text-white font-black text-sm shadow-[0_8px_24px_rgba(255,165,82,0.35)] flex items-center gap-2 transition-all">
                        <span>Shop all products</span>
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>

                    <a href="{{ route('products.index', ['category' => 'jewelry']) }}" class="btn-press px-6 py-3.5 rounded-2xl bg-white hover:bg-[#F9F3EA] text-[#2B1D1D] font-bold text-sm border-2 border-[#FFA552]/40 transition-all">
                        Browse jewelry &rarr;
                    </a>
                </div>

                <ul class="pt-4 flex flex-wrap items-center justify-center lg:justify-start gap-4 sm:gap-6 text-xs text-[#4A3333] font-semibold border-t border-[#EFE4D6]">
                    @foreach (['Flat $1.50 delivery in Phnom Penh', 'Pay by KHQR, ABA or cash', 'Prices in USD and riel'] as $point)
                    <li class="flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full bg-[#F9F3EA] text-[#FFA552] border border-[#FFA552] flex items-center justify-center">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        </span>
                        <span>{{ $point }}</span>
                    </li>
                    @endforeach
                </ul>
            </div>

            <!-- Hero Product Card -->
            @if ($hero)
            <div class="lg:col-span-5">
                <div class="relative max-w-md mx-auto">
                    <div class="bg-white rounded-3xl border-2 border-[#FFA552] p-5 sm:p-6 shadow-[0_20px_50px_rgba(43,29,29,0.1)] space-y-4 relative group">
                        <a href="{{ route('products.show', $hero->id) }}" class="aspect-square rounded-2xl bg-white border border-[#EFE4D6] overflow-hidden p-2 flex items-center justify-center relative">
                            <img
                                src="{{ $hero->image }}"
                                alt="{{ $hero->title }}"
                                class="w-full h-full object-cover rounded-xl group-hover:scale-105 transition-transform duration-700 ease-out"
                            />
                            <div class="absolute top-4 right-4 bg-[#FFA552] text-white px-3 py-1.5 rounded-lg font-black text-xs shadow-md">
                                ${{ number_format($hero->price_usd, 2) }} <span class="text-[10px] font-normal text-[#F9F3EA]">({{ number_format($hero->price_khr) }} ៛)</span>
                            </div>
                            @if ($hero->badge)
                            <div class="absolute bottom-4 left-4 bg-white border border-[#FFA552] px-2.5 py-1 rounded-md text-[11px] font-black text-[#2B1D1D] shadow-xs">
                                {{ $hero->badge }}
                            </div>
                            @endif
                        </a>

                        <div class="space-y-1.5 pt-1">
                            <span class="text-[11px] font-bold text-[#FFA552] uppercase tracking-wider">
                                {{ collect([$hero->material, $hero->category_label])->filter()->join(' • ') }}
                            </span>
                            <h3 class="text-base font-extrabold text-[#2B1D1D]">{{ $hero->title }}</h3>
                            @if ($hero->description)
                            <p class="text-xs text-stone-600 line-clamp-1">{{ $hero->description }}</p>
                            @endif
                        </div>

                        <button
                            type="button"
                            onclick="addToCart('{{ $hero->id }}', 1)"
                            class="btn-press w-full py-3 rounded-xl bg-[#FFA552] hover:bg-[#E88C35] text-white font-black text-xs shadow-md flex items-center justify-center gap-2 cursor-pointer transition-all"
                        >
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>Add to bag &bull; ${{ number_format($hero->price_usd, 2) }}</span>
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</section>

<!-- Category Strip -->
<nav class="bg-[#2B1D1D] text-[#F9F3EA] py-3.5 border-y border-[#3D2929]" aria-label="Categories">
    <ul class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center gap-x-6 gap-y-2 flex-wrap justify-center text-xs font-black uppercase tracking-widest">
        @foreach ($homeCategories as $key => $label)
            <li><a href="{{ route('products.index', ['category' => $key]) }}" class="hover:text-[#FFA552] transition-colors">{{ $label }}</a></li>
        @endforeach
    </ul>
</nav>

<!-- Product Grid -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-[#EFE4D6] pb-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-black text-[#2B1D1D] tracking-tight">New in the shop</h2>
            <p class="text-xs text-stone-500 mt-0.5">The latest {{ $featured->count() }} products. Filter by category below.</p>
        </div>

        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-bold">
            <button type="button" onclick="filterHomeProducts('all', this)" aria-pressed="true" class="category-chip px-4 py-2 rounded-lg bg-[#2B1D1D] text-white shrink-0">
                All ({{ $featured->count() }})
            </button>
            @foreach ($homeCategories as $key => $label)
                <button type="button" onclick="filterHomeProducts('{{ $key }}', this)" aria-pressed="false" class="category-chip px-4 py-2 rounded-lg bg-white text-[#2B1D1D] hover:bg-[#F9F3EA] border border-[#EFE4D6] transition-colors shrink-0">
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>

    <div id="homeProductGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6 pt-6">
        @foreach ($featured as $product)
            @include('partials.product-card', ['product' => $product])
        @endforeach
    </div>

    <p id="homeProductGridEmpty" class="{{ $featured->isEmpty() ? '' : 'hidden' }} py-16 text-center text-stone-500 text-sm">
        No products in this category yet.
    </p>

    <div class="text-center pt-10">
        <a href="{{ route('products.index') }}" class="btn-press inline-flex items-center gap-2 px-8 py-3.5 rounded-xl bg-[#FFA552] hover:bg-[#E88C35] text-white font-black text-xs shadow-md transition-all">
            <span>View the full catalog</span>
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
        </a>
    </div>
</section>
@endsection
