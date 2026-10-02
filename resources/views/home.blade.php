@extends('layouts.app')

@section('title', 'PsaOnline — Gen-Z Streetwear & Aesthetic Accessories')

@section('content')
<!-- Hero Section in Cotton Beige & Sorbet Orange -->
<section class="relative overflow-hidden py-10 sm:py-16 lg:py-20 bg-gradient-to-b from-[#FDFBF7] via-[#F9F3EA] to-[#FDFBF7]">
    <div class="hidden md:block absolute top-12 left-10 animate-float-wiggle">
        <div class="genz-sticker bg-white border border-[#FFA552] shadow-lg text-[#2B1D1D]">
            <span>✨</span> Aesthetic Fits Only
        </div>
    </div>

    <div class="hidden md:block absolute bottom-12 right-12 animate-float-wiggle" style="animation-delay: -2s;">
        <div class="genz-sticker bg-[#2B1D1D] text-[#F9F3EA] shadow-xl border border-[#FFA552]">
            <span>⚡</span> Bakong KHQR Verified
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-12 items-center">
            <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                <div class="reveal-init inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white border border-[#EFE4D6] shadow-xs text-xs font-extrabold text-[#2B1D1D]">
                    <span class="w-2 h-2 rounded-full bg-[#FFA552] animate-ping"></span>
                    <span>VIRAL ON TIKTOK & PINTEREST</span>
                    <span class="text-[#FFA552]">&bull;</span>
                    <span class="text-[#FFA552] font-black">Under $18 Everyday Drops</span>
                </div>

                <h1 class="reveal-init delay-100 text-3xl sm:text-5xl lg:text-6xl font-black text-[#2B1D1D] tracking-tight leading-[1.1]">
                    Accessories that make <br class="hidden sm:inline" />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-[#2B1D1D] via-[#FFA552] to-[#E88C35]">your everyday fit pop.</span>
                </h1>

                <p class="reveal-init delay-200 text-sm sm:text-base text-[#4A3333] max-w-xl mx-auto lg:mx-0 leading-relaxed font-medium">
                    Curated by our indie studio in Phnom Penh. Chunky Y2K rings, tarnish-free layered chains, 90s sunglasses, and puffy cloud bags. Clean aesthetics for Gen-Z.
                </p>

                <div class="reveal-init delay-300 flex flex-wrap items-center justify-center lg:justify-start gap-3.5 pt-2">
                    <a href="{{ route('products.index') }}" class="btn-press px-6 py-3.5 rounded-2xl bg-[#FFA552] hover:bg-[#E88C35] text-white font-black text-sm shadow-[0_8px_24px_rgba(255,165,82,0.35)] flex items-center gap-2 transition-all">
                        <span>Shop All Drops</span>
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                    
                    <a href="{{ route('products.index', ['category' => 'jewelry']) }}" class="btn-press px-6 py-3.5 rounded-2xl bg-white hover:bg-[#F9F3EA] text-[#2B1D1D] font-bold text-sm border-2 border-[#FFA552]/40 transition-all shadow-xs">
                        Explore Y2K Silver &rarr;
                    </a>
                </div>

                <div class="reveal-init delay-400 pt-4 flex flex-wrap items-center justify-center lg:justify-start gap-4 sm:gap-6 text-xs text-[#4A3333] font-semibold border-t border-[#EFE4D6]">
                    <div class="flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full bg-[#F9F3EA] text-[#FFA552] border border-[#FFA552] flex items-center justify-center text-[10px] font-black">&check;</span>
                        <span>100% Tarnish-Free Chains</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full bg-[#F9F3EA] text-[#FFA552] border border-[#FFA552] flex items-center justify-center text-[10px] font-black">&check;</span>
                        <span>Same-Day Phnom Penh Courier</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <span class="w-5 h-5 rounded-full bg-[#F9F3EA] text-[#FFA552] border border-[#FFA552] flex items-center justify-center text-[10px] font-black">&check;</span>
                        <span>Bakong & ABA Scan & Pay</span>
                    </div>
                </div>
            </div>

            <!-- Hero Showcase Product Card in Pure Clean White -->
            <div class="lg:col-span-5 reveal-init delay-200">
                <div class="relative max-w-md mx-auto">
                    <div class="bg-white rounded-3xl border-2 border-[#FFA552] p-5 sm:p-6 shadow-[0_20px_50px_rgba(43,29,29,0.1)] space-y-4 relative group">
                        <div class="aspect-square rounded-2xl bg-white border border-[#EFE4D6] overflow-hidden p-2 flex items-center justify-center relative">
                            <img
                                src="https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=700&q=80"
                                alt="Silver Chrome Star Pendant Necklace"
                                class="w-full h-full object-cover rounded-xl group-hover:scale-105 transition-transform duration-700 ease-out"
                            />
                            <div class="absolute top-4 right-4 bg-[#FFA552] text-white px-3 py-1.5 rounded-full font-black text-xs shadow-md">
                                $6.50 <span class="text-[10px] font-normal text-[#F9F3EA]">(26,650 ៛)</span>
                            </div>
                            <div class="absolute bottom-4 left-4 bg-white/95 backdrop-blur-md border border-[#FFA552] px-3 py-1 rounded-full text-[11px] font-black text-[#2B1D1D] shadow-sm">
                                ⚡ Weekly Bestseller
                            </div>
                        </div>

                        <div class="space-y-1.5 pt-1">
                            <div class="flex items-center justify-between">
                                <span class="text-[11px] font-bold text-[#FFA552] uppercase tracking-wider">Stainless Steel &bull; Unisex</span>
                                <div class="flex items-center gap-1 text-xs text-amber-500 font-bold">
                                    <span>★ 4.9</span>
                                    <span class="text-stone-400 font-normal">(142)</span>
                                </div>
                            </div>
                            <h3 class="text-base font-extrabold text-[#2B1D1D]">Silver Chrome Star Pendant Necklace</h3>
                            <p class="text-xs text-stone-600 line-clamp-1">Layered stainless steel cyber star with 45cm+5cm extension chain.</p>
                        </div>

                        <button
                            onclick="addToCart('genz-01', 1)"
                            class="btn-press w-full py-3 rounded-xl bg-gradient-to-r from-[#FFA552] to-[#E88C35] hover:from-[#E88C35] hover:to-[#FFA552] text-white font-black text-xs shadow-md flex items-center justify-center gap-2 cursor-pointer transition-all"
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
<div class="bg-[#2B1D1D] text-[#F9F3EA] py-3.5 border-y border-[#3D2929] overflow-hidden select-none w-full">
    <div class="animate-marquee flex items-center text-[11px] sm:text-xs font-black uppercase tracking-widest">
        <!-- Marquee Track 1 -->
        <div class="flex items-center shrink-0 gap-6 sm:gap-8 pr-6 sm:pr-8">
            <span class="inline-flex items-center gap-2.5 whitespace-nowrap">
                <span class="w-6 h-6 rounded-full bg-[#FFA552]/15 border border-[#FFA552]/40 flex items-center justify-center text-[#FFA552] shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.912 5.813a2 2 0 001.275 1.275L21 12l-5.813 1.912a2 2 0 00-1.275 1.275L12 21l-1.912-5.813a2 2 0 00-1.275-1.275L3 12l5.813-1.912a2 2 0 001.275-1.275L12 3z"/></svg>
                </span>
                <span>LIQUID CHROME 316L ARMOR <span class="text-[#FFA552] font-extrabold">— SWEAT &amp; WATERPROOF</span></span>
            </span>
            <svg class="w-3 h-3 text-[#FFA552]/60 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z"/></svg>

            <span class="inline-flex items-center gap-2.5 whitespace-nowrap">
                <span class="w-6 h-6 rounded-full bg-[#FFA552]/15 border border-[#FFA552]/40 flex items-center justify-center text-[#FFA552] shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12h2m16 0h2M4 12a4 4 0 014-4h1a3 3 0 013 3v1a2 2 0 01-2 2H6a2 2 0 01-2-2v0zm10 0a3 3 0 013-3h1a4 4 0 014 4v0a2 2 0 01-2 2h-4a2 2 0 01-2-2v-1zm-2-1h2"/></svg>
                </span>
                <span>UV400 VINTAGE TINTED OPTICS <span class="text-[#FFA552] font-extrabold">— BUILT FOR GOLDEN HOUR</span></span>
            </span>
            <svg class="w-3 h-3 text-[#FFA552]/60 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z"/></svg>

            <span class="inline-flex items-center gap-2.5 whitespace-nowrap">
                <span class="w-6 h-6 rounded-full bg-[#FFA552]/15 border border-[#FFA552]/40 flex items-center justify-center text-[#FFA552] shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </span>
                <span>ULTRA-PLUSH CLOUD TOTES <span class="text-[#FFA552] font-extrabold">— WEIGHTLESS DAILY CARRY</span></span>
            </span>
            <svg class="w-3 h-3 text-[#FFA552]/60 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z"/></svg>

            <span class="inline-flex items-center gap-2.5 whitespace-nowrap">
                <span class="w-6 h-6 rounded-full bg-[#FFA552]/15 border border-[#FFA552]/40 flex items-center justify-center text-[#FFA552] shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </span>
                <span>1-OF-1 ARTISAN TECH CHARMS <span class="text-[#FFA552] font-extrabold">— HAND-STRUNG IN PHNOM PENH</span></span>
            </span>
            <svg class="w-3 h-3 text-[#FFA552]/60 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z"/></svg>

            <span class="inline-flex items-center gap-2.5 whitespace-nowrap">
                <span class="w-6 h-6 rounded-full bg-[#FFA552]/15 border border-[#FFA552]/40 flex items-center justify-center text-[#FFA552] shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </span>
                <span>INSTANT BAKONG KHQR &amp; ABA PAY <span class="text-[#FFA552] font-extrabold">— 1-TAP SCAN CHECKOUT</span></span>
            </span>
            <svg class="w-3 h-3 text-[#FFA552]/60 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z"/></svg>

            <span class="inline-flex items-center gap-2.5 whitespace-nowrap">
                <span class="w-6 h-6 rounded-full bg-[#FFA552]/15 border border-[#FFA552]/40 flex items-center justify-center text-[#FFA552] shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                </span>
                <span>SMALL-BATCH STUDIO DROPS <span class="text-[#FFA552] font-extrabold">— VIRAL FITS UNDER $18</span></span>
            </span>
            <svg class="w-3 h-3 text-[#FFA552]/60 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z"/></svg>
        </div>

        <!-- Marquee Track 2 -->
        <div class="flex items-center shrink-0 gap-6 sm:gap-8 pr-6 sm:pr-8" aria-hidden="true">
            <span class="inline-flex items-center gap-2.5 whitespace-nowrap">
                <span class="w-6 h-6 rounded-full bg-[#FFA552]/15 border border-[#FFA552]/40 flex items-center justify-center text-[#FFA552] shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.912 5.813a2 2 0 001.275 1.275L21 12l-5.813 1.912a2 2 0 00-1.275 1.275L12 21l-1.912-5.813a2 2 0 00-1.275-1.275L3 12l5.813-1.912a2 2 0 001.275-1.275L12 3z"/></svg>
                </span>
                <span>LIQUID CHROME 316L ARMOR <span class="text-[#FFA552] font-extrabold">— SWEAT &amp; WATERPROOF</span></span>
            </span>
            <svg class="w-3 h-3 text-[#FFA552]/60 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z"/></svg>

            <span class="inline-flex items-center gap-2.5 whitespace-nowrap">
                <span class="w-6 h-6 rounded-full bg-[#FFA552]/15 border border-[#FFA552]/40 flex items-center justify-center text-[#FFA552] shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2 12h2m16 0h2M4 12a4 4 0 014-4h1a3 3 0 013 3v1a2 2 0 01-2 2H6a2 2 0 01-2-2v0zm10 0a3 3 0 013-3h1a4 4 0 014 4v0a2 2 0 01-2 2h-4a2 2 0 01-2-2v-1zm-2-1h2"/></svg>
                </span>
                <span>UV400 VINTAGE TINTED OPTICS <span class="text-[#FFA552] font-extrabold">— BUILT FOR GOLDEN HOUR</span></span>
            </span>
            <svg class="w-3 h-3 text-[#FFA552]/60 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z"/></svg>

            <span class="inline-flex items-center gap-2.5 whitespace-nowrap">
                <span class="w-6 h-6 rounded-full bg-[#FFA552]/15 border border-[#FFA552]/40 flex items-center justify-center text-[#FFA552] shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </span>
                <span>ULTRA-PLUSH CLOUD TOTES <span class="text-[#FFA552] font-extrabold">— WEIGHTLESS DAILY CARRY</span></span>
            </span>
            <svg class="w-3 h-3 text-[#FFA552]/60 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z"/></svg>

            <span class="inline-flex items-center gap-2.5 whitespace-nowrap">
                <span class="w-6 h-6 rounded-full bg-[#FFA552]/15 border border-[#FFA552]/40 flex items-center justify-center text-[#FFA552] shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                </span>
                <span>1-OF-1 ARTISAN TECH CHARMS <span class="text-[#FFA552] font-extrabold">— HAND-STRUNG IN PHNOM PENH</span></span>
            </span>
            <svg class="w-3 h-3 text-[#FFA552]/60 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z"/></svg>

            <span class="inline-flex items-center gap-2.5 whitespace-nowrap">
                <span class="w-6 h-6 rounded-full bg-[#FFA552]/15 border border-[#FFA552]/40 flex items-center justify-center text-[#FFA552] shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </span>
                <span>INSTANT BAKONG KHQR &amp; ABA PAY <span class="text-[#FFA552] font-extrabold">— 1-TAP SCAN CHECKOUT</span></span>
            </span>
            <svg class="w-3 h-3 text-[#FFA552]/60 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z"/></svg>

            <span class="inline-flex items-center gap-2.5 whitespace-nowrap">
                <span class="w-6 h-6 rounded-full bg-[#FFA552]/15 border border-[#FFA552]/40 flex items-center justify-center text-[#FFA552] shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                </span>
                <span>SMALL-BATCH STUDIO DROPS <span class="text-[#FFA552] font-extrabold">— VIRAL FITS UNDER $18</span></span>
            </span>
            <svg class="w-3 h-3 text-[#FFA552]/60 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z"/></svg>
        </div>
    </div>
</div>

<!-- Category Drops Grid -->
<section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 border-b border-[#EFE4D6] pb-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-black text-[#2B1D1D] tracking-tight">Explore the Drops</h2>
            <p class="text-xs text-stone-500 mt-0.5">Filter trendy pieces for your aesthetic.</p>
        </div>

        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-bold">
            <button onclick="filterHomeProducts('all', this)" class="category-chip px-4 py-2 rounded-full bg-[#2B1D1D] text-white shadow-xs shrink-0">
                All (12)
            </button>
            <button onclick="filterHomeProducts('jewelry', this)" class="category-chip px-4 py-2 rounded-full bg-white text-[#2B1D1D] hover:bg-[#F9F3EA] border border-[#EFE4D6] transition-colors shrink-0">
                Y2K Jewelry
            </button>
            <button onclick="filterHomeProducts('eyewear', this)" class="category-chip px-4 py-2 rounded-full bg-white text-[#2B1D1D] hover:bg-[#F9F3EA] border border-[#EFE4D6] transition-colors shrink-0">
                Shades
            </button>
            <button onclick="filterHomeProducts('bags', this)" class="category-chip px-4 py-2 rounded-full bg-white text-[#2B1D1D] hover:bg-[#F9F3EA] border border-[#EFE4D6] transition-colors shrink-0">
                Cloud Bags
            </button>
            <button onclick="filterHomeProducts('hair', this)" class="category-chip px-4 py-2 rounded-full bg-white text-[#2B1D1D] hover:bg-[#F9F3EA] border border-[#EFE4D6] transition-colors shrink-0">
                Clips & Pins
            </button>
        </div>
    </div>

    <!-- Live Products Grid (Rendered dynamically) -->
    <div id="homeProductGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6 pt-6"></div>

    <div class="text-center pt-10">
        <a href="{{ route('products.index') }}" class="btn-press inline-flex items-center gap-2 px-8 py-3.5 rounded-full bg-[#FFA552] hover:bg-[#E88C35] text-white font-black text-xs shadow-md transition-all">
            <span>View Full Catalog (All Accessories)</span>
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
        </a>
    </div>
</section>
@endsection
