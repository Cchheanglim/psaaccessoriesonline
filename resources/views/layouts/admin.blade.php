<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Admin | PsaOnlineAccessories')</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-[#FDFBF7] text-[#2B1D1D] font-sans min-h-screen flex flex-col antialiased selection:bg-[#FFA552] selection:text-white">
@php
    $adminUser = auth()->user();
    $adminLinks = [
        ['admin.dashboard', 'admin.dashboard', 'Dashboard', false],
        ['admin.orders.index', 'admin.orders.*', 'Orders', false],
        ['admin.products.index', 'admin.products.*', 'Products', false],
        ['admin.users.index', 'admin.users.*', 'Users', true],
        ['admin.payment-methods.index', 'admin.payment-methods.*', 'Payment methods', true],
    ];
@endphp

    <!-- Top Admin Bar -->
    <header class="sticky top-0 z-40 bg-[#FDFBF7]/90 backdrop-blur-md border-b border-[#EFE4D6]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 shrink-0">
                <div class="w-8 h-8 rounded-xl bg-[#2B1D1D] flex items-center justify-center font-black text-[#FFA552] text-base">P</div>
                <span class="font-black text-lg text-[#2B1D1D]">Psa<span class="text-[#FFA552]">Online</span> <span class="text-xs uppercase bg-[#F9F3EA] px-2 py-0.5 rounded-md text-[#FFA552] font-black border border-[#FFA552]">Admin</span></span>
            </a>

            <nav class="hidden md:flex items-center gap-2 text-xs font-bold" aria-label="Admin">
                @foreach ($adminLinks as [$route, $pattern, $label, $adminOnly])
                    @continue($adminOnly && ! $adminUser->isAdmin())
                    <a href="{{ route($route) }}" class="px-3 py-1.5 rounded-lg {{ request()->routeIs($pattern) ? 'bg-[#2B1D1D] text-white' : 'text-stone-600 hover:text-[#2B1D1D]' }}">{{ $label }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-3 text-xs">
                <span class="hidden lg:inline text-stone-500">{{ $adminUser->name }} &bull; {{ ucfirst($adminUser->role) }}</span>
                <a href="{{ route('home') }}" target="_blank" rel="noopener" class="px-3 py-1.5 rounded-lg bg-[#FFA552] hover:bg-[#E88C35] text-white font-black flex items-center gap-1.5">
                    <span>View store</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3 py-1.5 rounded-lg border border-[#EFE4D6] text-stone-600 hover:text-[#2B1D1D] font-bold">Sign out</button>
                </form>
            </div>
        </div>

        <!-- Small-screen navigation -->
        <nav class="md:hidden flex items-center gap-2 overflow-x-auto px-4 pb-3 text-xs font-bold" aria-label="Admin">
            @foreach ($adminLinks as [$route, $pattern, $label, $adminOnly])
                @continue($adminOnly && ! $adminUser->isAdmin())
                <a href="{{ route($route) }}" class="shrink-0 px-3 py-1.5 rounded-lg {{ request()->routeIs($pattern) ? 'bg-[#2B1D1D] text-white' : 'bg-white border border-[#EFE4D6] text-stone-600' }}">{{ $label }}</a>
            @endforeach
        </nav>
    </header>

    <main class="flex-1 max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-6">
        @if (session('success'))
            <div role="status" class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-bold text-emerald-800">
                {{ session('success') }}
            </div>
        @endif
        @if ($errors->any())
            <div role="alert" class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-700">
                <ul class="list-disc pl-4 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
