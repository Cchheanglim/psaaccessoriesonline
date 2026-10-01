@extends('layouts.app')

@section('title', 'PsaOnline — Gen-Z Streetwear & Aesthetic Accessories')

@section('content')
<!-- Hero Section in Cotton Beige & Sorbet Orange -->
<section class="relative overflow-hidden py-10 sm:py-16 lg:py-20 bg-gradient-to-b from-[#F5F5F5] via-[#FFF3EC] to-[#F5F5F5]">
    <div class="hidden md:block absolute top-12 left-10 animate-float-wiggle">
        <div class="genz-sticker bg-white border border-[#FF5000] shadow-lg text-[#333333]">
            <span>✨</span> Aesthetic Fits Only
        </div>
    </div>

    <div class="hidden md:block absolute bottom-12 right-12 animate-float-wiggle" style="animation-delay: -2s;">
        <div class="genz-sticker bg-[#333333] text-[#FFF3EC] shadow-xl border border-[#FF5000]">
            <span>⚡</span> Bakong KHQR Verified
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <div class="reveal-init inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white border border-[#EDEDED] shadow-xs text-xs font-extrabold text-[#333333]">
                    <span class="w-2 h-2 rounded-full bg-[#FF5000] animate-ping"></span>
                    <span>VIRAL ON TIKTOK & PINTEREST</span>
                    <span class="text-[#FF5000]">&bull;</span>
                    <span class="text-[#FF5000] font-black">Under $18 Everyday Drops</span>
                </div>

                <h1 class="reveal-init delay-100 text-3xl sm:text-5xl lg:text-6xl font-black text-[#333333] tracking-tight leading-[1.1]">
                    Accessories that make <br class="hidden sm:inline" />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#333333] via-[#FF5000] to-[#E64500]">your everyday fit pop.</span>
                </h1>

                <p class="reveal-init delay-200 text-sm sm:text-base text-[#666666] max-w-xl mx-auto lg:mx-0 leading-relaxed font-medium">
                    Curated by our indie studio in Phnom Penh. Chunky Y2K rings, tarnish-free layered chains, 90s sunglasses, and puffy cloud bags. Clean aesthetics for Gen-Z.
                </p>

                <div class="reveal-init delay-300 flex flex-wrap items-center justify-center lg:justify-start gap-3.5 pt-2">
                    <a href="{{ route('products.index') }}" class="btn-press px-6 py-3.5 rounded-2xl bg-[#FF5000] hover:bg-[#E64500] text-white font-black text-sm shadow-[0_8px_24px_rgba(255, 80, 0,0.35)] flex items-center gap-2 transition-all">
                        <span>Shop All Drops</span>
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                    
                    <a href="{{ route('products.index', ['category' => 'jewelry']) }}" class="btn-press px-6 py-3.5 rounded-2xl bg-white hover:bg-[#FFF3EC] text-[#333333] font-bold text-sm border-2 border-[#FF5000]/40 transition-all shadow-xs">
                        Explore Y2K Silver &rarr;
                    </a>
                </div>

                <div class="reveal-init delay-400 pt-4 flex flex-wrap items-center justify-center lg:justify-start gap-4 sm:gap-6 text-xs text-[#666666] font-semibold border-t border-[#EDEDED]">
                    <div class="flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full bg-[#FFF3EC] text-[#FF5000] border border-[#FF5000] flex items-center justify-center text-[10px] font-black">&check;</span>
                        <span>100% Tarnish-Free Chains</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full bg-[#FFF3EC] text-[#FF5000] border border-[#FF5000] flex items-center justify-center text-[10px] font-black">&check;</span>
                        <span>Same-Day Phnom Penh Courier</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full bg-[#FFF3EC] text-[#FF5000] border border-[#FF5000] flex items-center justify-center text-[10px] font-black">&check;</span>
                        <span>Bakong & ABA Scan & Pay</span>
                    </div>
                </div>
            </div>

            <!-- Hero Showcase Product Card in Pure Clean White -->
            <div class="lg:col-span-5 reveal-init delay-200">
                <div class="relative max-w-md mx-auto">
                    <div class="bg-white rounded-3xl border-2 border-[#FF5000] p-5 sm:p-6 shadow-[0_20px_50px_rgba(0, 0, 0,0.1)] space-y-4 relative group">
                        <div class="aspect-square rounded-2xl bg-white border border-[#EDEDED] overflow-hidden p-2 flex items-center justify-center relative">
                            <img
                                src="https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=700&q=80"
                                alt="Silver Chrome Star Pendant Necklace"
                                class="w-full h-full object-cover rounded-xl group-hover:scale-105 transition-transform duration-700 ease-out"
                            />
                            <div class="absolute top-4 right-4 bg-[#FF5000] text-white px-3 py-1.5 rounded-full font-black text-xs shadow-md">
                                $6.50 <span class="text-[10px] font-normal text-[#FFF3EC]">(26,650 ៛)</span>
                            </div>
                            <div class="absolute bottom-4 left-4 bg-white/95 backdrop-blur-md border border-[#FF5000] px-3 py-1 rounded-full text-[11px] font-black text-[#333333] shadow-sm">
                                ⚡ Weekly Bestseller
                            </div>
                        </div>

                        <div class="space-y-1.5 pt-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-[#FF5000] uppercase tracking-wider">Stainless Steel &bull; Unisex</span>
                                <div class="flex items-center gap-1 text-xs text-amber-500 font-bold">
                                    <span>★ 4.9</span>
                                    <span class="text-stone-400 font-normal">(142)</span>
                                </div>
                            </div>
                            <h3 class="text-base font-extrabold text-[#333333]">Silver Chrome Star Pendant Necklace</h3>
                            <p class="text-xs text-stone-600 line-clamp-1">Layered stainless steel cyber star with 45cm+5cm extension chain.</p>
                        </div>

                        <button
                            onclick="addToCart('genz-01', 1)"
                            class="btn-press w-full py-3 rounded-xl bg-gradient-to-r from-[#FF5000] to-[#E64500] hover:from-[#E64500] hover:to-[#FF5000] text-white font-black text-xs shadow-md flex items-center justify-center gap-2 cursor-pointer transition-all"
                        >
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>Add to Bag &bull; $6.50</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Continuous Marquee Ticker -->
<div class="bg-[#333333] text-[#FFF3EC] py-3.5 border-y border-[#4D4D4D] overflow-hidden select-none">
    <div class="animate-marquee flex items-center gap-8 text-xs font-black uppercase tracking-widest">
        <span class="flex items-center gap-2"><span class="text-[#FF5000]">✨</span> Y2K CYBER JEWELRY</span>
        <span class="text-[#FF5000]">&bull;</span>
        <span class="flex items-center gap-2"><span class="text-[#FF5000]">🕶️</span> 90s RETRO SHADES</span>
        <span class="text-[#FF5000]">&bull;</span>
        <span class="flex items-center gap-2"><span class="text-[#FF5000]">☁️</span> PUFFY CLOUD BAGS</span>
        <span class="text-[#FF5000]">&bull;</span>
        <span class="flex items-center gap-2"><span class="text-[#FF5000]">💖</span> HAND-STRUNG PHONE CHARMS</span>
        <span class="text-[#FF5000]">&bull;</span>
        <span class="flex items-center gap-2"><span class="text-[#FF5000]">⚡</span> BAKONG UNIVERSAL KHQR</span>
        <span class="text-[#FF5000]">&bull;</span>
        <span class="flex items-center gap-2"><span class="text-[#FF5000]">🍒</span> MATTE CLAW CLIPS</span>
    </div>
</div>

<!-- Category Drops Grid -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
    <div class="flex items-center justify-between gap-4 border-b border-[#EDEDED] pb-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-black text-[#333333] tracking-tight">Explore the Drops</h2>
            <p class="text-xs text-stone-500 mt-0.5">Filter trendy pieces for your aesthetic.</p>
        </div>

        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-bold">
            <button onclick="filterHomeProducts('all', this)" class="category-chip px-4 py-2 rounded-full bg-[#333333] text-white shadow-xs shrink-0">
                All (12)
            </button>
            <button onclick="filterHomeProducts('jewelry', this)" class="category-chip px-4 py-2 rounded-full bg-white text-[#333333] hover:bg-[#FFF3EC] border border-[#EDEDED] transition-colors shrink-0">
                Y2K Jewelry
            </button>
            <button onclick="filterHomeProducts('eyewear', this)" class="category-chip px-4 py-2 rounded-full bg-white text-[#333333] hover:bg-[#FFF3EC] border border-[#EDEDED] transition-colors shrink-0">
                Shades
            </button>
            <button onclick="filterHomeProducts('bags', this)" class="category-chip px-4 py-2 rounded-full bg-white text-[#333333] hover:bg-[#FFF3EC] border border-[#EDEDED] transition-colors shrink-0">
                Cloud Bags
            </button>
            <button onclick="filterHomeProducts('hair', this)" class="category-chip px-4 py-2 rounded-full bg-white text-[#333333] hover:bg-[#FFF3EC] border border-[#EDEDED] transition-colors shrink-0">
                Clips & Pins
            </button>
        </div>
    </div>

    <!-- Live Products Grid (Rendered dynamically) -->
    <div id="homeProductGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6 pt-6"></div>

    <div class="text-center pt-10">
        <a href="{{ route('products.index') }}" class="btn-press inline-flex items-center gap-2 px-8 py-3.5 rounded-full bg-[#FF5000] hover:bg-[#E64500] text-white font-black text-xs shadow-md transition-all">
            <span>View Full Catalog (All Accessories)</span>
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
        </a>
    </div>
</section>
@endsection
