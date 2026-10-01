<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PsaOnline — Gen-Z Streetwear & Aesthetic Accessories')</title>
    <meta name="description" content="Shop viral Gen-Z aesthetic accessories: Y2K silver chrome jewelry, 90s sunglasses, puffy cloud bags, aesthetic claw clips, and phone charms in Phnom Penh." />
    <meta property="og:title" content="@yield('title', 'PsaOnline — Gen-Z Aesthetic Accessories')" />
    <meta property="og:description" content="Affordable viral streetwear and aesthetic accessories with Bakong Universal KHQR." />
    
    <!-- Scripts & Styles via Vite / Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
    @stack('styles')
</head>
<body class="bg-[#FDFBF7] text-[#2B1D1D] font-sans min-h-screen flex flex-col antialiased selection:bg-[#FFA552] selection:text-white pb-mobile-nav">

    <!-- Ambient Top Sorbet Glow Bar -->
    <div class="fixed top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-48 bg-hero-glow pointer-events-none -z-10"></div>

    <!-- Announcement Bar (Gen-Z Trendy Micro Ticker in Deep Espresso & Sorbet) -->
    <div class="bg-[#2B1D1D] text-[#F9F3EA] py-2 px-4 text-xs font-semibold tracking-wide border-b border-[#3D2929] relative z-50 overflow-hidden">
        <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
            <div class="flex items-center gap-2 overflow-hidden">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-[#FFA552] text-[#2B1D1D] text-[10px] font-black uppercase tracking-wider animate-pulse-glow">
                    ⚡ Sorbet Drops
                </span>
                <span class="truncate text-[11px] sm:text-xs text-[#F9F3EA]/95">
                    New Y2K Star Chains & 90s Tinted Shades are live! Free Phnom Penh delivery over $15 🛵
                </span>
            </div>

            <div class="hidden md:flex items-center gap-4 shrink-0 text-[11px] text-[#FFA552]">
                <span class="text-[#F9F3EA]">Phnom Penh 1-2hr Express Dispatch</span>
                <span>&bull;</span>
                <span class="text-[#FFA552] font-black">Universal KHQR Accepted</span>
            </div>
        </div>
    </div>

    <!-- Main Navigation Header in Cotton Beige -->
    <header class="sticky top-0 z-40 bg-[#FDFBF7]/90 backdrop-blur-md border-b border-[#EFE4D6] transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 sm:h-20 flex items-center justify-between gap-4">
            
            <!-- Brand Logo -->
            <a href="{{ route('home') }}" class="flex items-center gap-2.5 sm:gap-3 group">
                <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-2xl bg-gradient-to-br from-[#2B1D1D] via-[#3D2929] to-[#FFA552] flex items-center justify-center text-white font-black text-xl shadow-[0_4px_16px_rgba(255,165,82,0.25)] group-hover:scale-105 transition-transform duration-300 border border-[#FFA552]/40">
                    P
                </div>
                <div class="flex flex-col">
                    <div class="text-xl sm:text-2xl font-black text-[#2B1D1D] tracking-tight leading-none">
                        Psa<span class="text-[#FFA552]">Online</span>
                    </div>
                    <span class="text-[9px] uppercase tracking-widest text-[#FFA552] font-black mt-0.5">
                        Gen-Z Accessories &bull; Studio
                    </span>
                </div>
            </a>

            <!-- Search Bar -->
            <div class="flex-1 max-w-md hidden lg:block">
                <form action="{{ route('products.index') }}" method="GET" class="relative">
                    <input
                        type="text"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Search Y2K rings, cloud bags, claw clips, shades..."
                        class="w-full pl-10 pr-4 py-2.5 bg-white hover:bg-white focus:bg-white text-xs border border-[#EFE4D6] focus:border-[#FFA552] rounded-full focus:outline-none focus:ring-2 focus:ring-[#FFA552]/20 transition-all text-[#2B1D1D] placeholder-stone-400"
                    />
                    <div class="absolute left-3.5 top-1/2 -translate-y-1/2 text-[#FFA552]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                </form>
            </div>

            <!-- Header Actions -->
            <div class="flex items-center gap-2 sm:gap-4">
                <nav class="hidden md:flex items-center gap-1 text-xs font-extrabold text-[#2B1D1D]">
                    <a href="{{ route('products.index') }}" class="px-3 py-1.5 rounded-full hover:bg-[#F9F3EA] hover:text-[#FFA552] transition-colors">All Drops</a>
                    <a href="{{ route('products.index', ['category' => 'jewelry']) }}" class="px-3 py-1.5 rounded-full hover:bg-[#F9F3EA] hover:text-[#FFA552] transition-colors">Y2K Jewelry</a>
                    <a href="{{ route('products.index', ['category' => 'eyewear']) }}" class="px-3 py-1.5 rounded-full hover:bg-[#F9F3EA] hover:text-[#FFA552] transition-colors">Shades</a>
                    <a href="{{ route('products.index', ['category' => 'bags']) }}" class="px-3 py-1.5 rounded-full hover:bg-[#F9F3EA] hover:text-[#FFA552] transition-colors">Cloud Bags</a>
                    <a href="{{ route('products.index', ['category' => 'hair']) }}" class="px-3 py-1.5 rounded-full hover:bg-[#F9F3EA] hover:text-[#FFA552] transition-colors">Hair & Clips</a>
                </nav>

                <!-- Currency Selector Pill -->
                <div class="flex items-center bg-[#F9F3EA] p-0.5 rounded-full border border-[#EFE4D6] text-[11px]">
                    <button type="button" onclick="setCurrency('USD')" data-currency="USD" class="currency-toggle-btn px-2.5 py-1 font-black rounded-full transition-all bg-[#2B1D1D] text-white shadow-xs">USD</button>
                    <button type="button" onclick="setCurrency('KHR')" data-currency="KHR" class="currency-toggle-btn px-2.5 py-1 font-black rounded-full transition-all text-[#2B1D1D]">KHR</button>
                </div>

                <!-- Bag Button -->
                <a href="{{ route('cart.index') }}" class="relative p-2.5 rounded-full bg-white hover:bg-[#F9F3EA] border border-[#EFE4D6] text-[#2B1D1D] transition-transform hover:scale-105" title="Bag">
                    <svg class="w-5 h-5 text-[#2B1D1D]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                    <span class="cart-count-badge absolute -top-1 -right-1 bg-[#FFA552] text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center ring-2 ring-white shadow-sm hidden">0</span>
                </a>

                <!-- Account -->
                <a href="{{ route('buyer.dashboard') }}" class="p-1 rounded-full hover:ring-2 hover:ring-[#FFA552] transition flex items-center gap-1.5 text-xs font-bold text-[#2B1D1D]">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#FFA552] to-[#2B1D1D] text-white font-black flex items-center justify-center text-xs shadow-xs">
                        SC
                    </div>
                    <span class="hidden xl:inline text-xs font-semibold">Account</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer in Cotton Beige -->
    <footer class="bg-[#F9F3EA] border-t border-[#EFE4D6] mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="col-span-2 md:col-span-1 space-y-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-xl bg-[#2B1D1D] text-white font-black text-base flex items-center justify-center">P</div>
                        <span class="text-lg font-black text-[#2B1D1D]">Psa<span class="text-[#FFA552]">Online</span></span>
                    </div>
                    <p class="text-xs text-stone-600 leading-relaxed">
                        Gen-Z streetwear accessories, cyber Y2K jewelry, and everyday aesthetic essentials based in Phnom Penh, Cambodia.
                    </p>
                    <div class="text-[11px] font-bold text-[#FFA552]">
                        Telegram Support: <a href="https://t.me/psaonline_support" target="_blank" class="underline">@psaonline_support</a>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="text-xs font-bold uppercase tracking-wider text-[#2B1D1D]">Shop Categories</div>
                    <ul class="text-xs space-y-1.5 text-stone-600">
                        <li><a href="{{ route('products.index', ['category' => 'jewelry']) }}" class="hover:text-[#FFA552]">Y2K & Cyber Jewelry</a></li>
                        <li><a href="{{ route('products.index', ['category' => 'eyewear']) }}" class="hover:text-[#FFA552]">Shades & Eyewear</a></li>
                        <li><a href="{{ route('products.index', ['category' => 'bags']) }}" class="hover:text-[#FFA552]">Puffy Cloud Bags</a></li>
                        <li><a href="{{ route('products.index', ['category' => 'hair']) }}" class="hover:text-[#FFA552]">Claw Clips & Pins</a></li>
                    </ul>
                </div>

                <div class="space-y-2">
                    <div class="text-xs font-bold uppercase tracking-wider text-[#2B1D1D]">Orders & Delivery</div>
                    <ul class="text-xs space-y-1.5 text-stone-600">
                        <li><a href="{{ route('cart.index') }}" class="hover:text-[#FFA552]">Shopping Bag</a></li>
                        <li><a href="{{ route('checkout.index') }}" class="hover:text-[#FFA552]">Checkout & KHQR</a></li>
                        <li><a href="{{ route('buyer.dashboard') }}" class="hover:text-[#FFA552]">Track My Order</a></li>
                        <li><a href="{{ route('admin.dashboard') }}" class="hover:text-[#FFA552]">Admin Portal</a></li>
                    </ul>
                </div>

                <div class="space-y-2">
                    <div class="text-xs font-bold uppercase tracking-wider text-[#2B1D1D]">Accepted Payments</div>
                    <div class="p-3 bg-white border border-[#EFE4D6] rounded-xl space-y-2 text-xs">
                        <div class="font-bold text-[#2B1D1D] flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-red-600"></span>
                            Bakong Universal KHQR
                        </div>
                        <p class="text-[11px] text-stone-500">Scan via ABA, ACLEDA, Canadia, Wing, or any Cambodian mobile banking app.</p>
                        <div class="text-[11px] text-[#FFA552] font-black">Dual Currency: 1 USD = 4,100 KHR</div>
                    </div>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-[#EFE4D6] flex flex-col sm:flex-row items-center justify-between text-xs text-stone-500 gap-2">
                <div>&copy; 2026 PsaOnline &bull; Laravel 12 Web II Project.</div>
                <div class="flex items-center gap-4 text-[11px]">
                    <a href="#" class="hover:underline">Privacy Policy</a>
                    <span>&bull;</span>
                    <a href="#" class="hover:underline">Terms of Service</a>
                    <span>&bull;</span>
                    <span>Made for Gen-Z in Cambodia 🇰🇭</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Mobile Bottom Navigation Bar -->
    <nav class="md:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-lg border-t border-[#EFE4D6] px-4 py-2 flex items-center justify-around text-center select-none shadow-[0_-4px_20px_rgba(43,29,29,0.06)]">
        <a href="{{ route('home') }}" class="flex flex-col items-center gap-1 text-[#2B1D1D] font-bold text-[10px]">
            <svg class="w-5 h-5 text-[#FFA552]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
            <span>Store</span>
        </a>
        <a href="{{ route('products.index') }}" class="flex flex-col items-center gap-1 text-stone-500 hover:text-[#2B1D1D] text-[10px]">
            <svg class="w-5 h-5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span>Catalog</span>
        </a>
        <a href="{{ route('cart.index') }}" class="flex flex-col items-center gap-1 text-stone-500 hover:text-[#2B1D1D] text-[10px]">
            <div class="relative">
                <svg class="w-5 h-5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                <span class="mobile-cart-badge hidden absolute -top-1.5 -right-2 bg-[#FFA552] text-white text-[9px] font-black rounded-full w-4 h-4 flex items-center justify-center">0</span>
            </div>
            <span>Bag</span>
        </a>
        <a href="{{ route('buyer.dashboard') }}" class="flex flex-col items-center gap-1 text-stone-500 hover:text-[#2B1D1D] text-[10px]">
            <svg class="w-5 h-5 text-stone-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>Account</span>
        </a>
    </nav>

    <script src="{{ asset('assets/js/store.js') }}"></script>
    @stack('scripts')
</body>
</html>
