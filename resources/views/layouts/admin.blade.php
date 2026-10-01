<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0" />
    <title>@yield('title', 'Admin Operations Portal — PsaOnline')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}" />
    @stack('styles')
</head>
<body class="bg-[#F5F5F5] text-[#333333] font-sans min-h-screen flex flex-col antialiased selection:bg-[#FF5000] selection:text-white">

    <!-- Top Admin Bar in Cotton Beige -->
    <header class="sticky top-0 z-40 bg-white/90 backdrop-blur-md border-b border-[#EDEDED]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#333333] to-[#FF5000] flex items-center justify-center font-black text-white text-base">P</div>
                    <span class="font-black text-lg text-[#333333]">Psa<span class="text-[#FF5000]">Online</span> <span class="text-xs uppercase bg-[#FFF3EC] px-2 py-0.5 rounded-full text-[#FF5000] font-black border border-[#FF5000]">Admin</span></span>
                </a>
            </div>

            <nav class="hidden md:flex items-center gap-2 text-xs font-bold">
                <a href="{{ route('admin.dashboard') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-[#333333] text-white' : 'text-stone-600 hover:text-[#333333]' }}">Dashboard</a>
                <a href="{{ route('admin.orders.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('admin.orders.*') ? 'bg-[#333333] text-white' : 'text-stone-600 hover:text-[#333333]' }}">Orders & Slips</a>
                <a href="{{ route('admin.products.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('admin.products.*') ? 'bg-[#333333] text-white' : 'text-stone-600 hover:text-[#333333]' }}">Product Drops</a>
                <a href="{{ route('admin.users.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-[#333333] text-white' : 'text-stone-600 hover:text-[#333333]' }}">Users & Staff</a>
                <a href="{{ route('admin.payment-methods.index') }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs('admin.payment-methods.*') ? 'bg-[#333333] text-white' : 'text-stone-600 hover:text-[#333333]' }}">KHQR Config</a>
            </nav>

            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" target="_blank" class="px-3 py-1 rounded-full bg-[#FF5000] hover:bg-[#E64500] text-xs text-white font-black flex items-center gap-1.5">
                    <span>View Store</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>
        </div>
    </header>

    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
