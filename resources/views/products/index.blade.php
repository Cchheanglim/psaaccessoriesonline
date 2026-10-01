@extends('layouts.app')

@section('title', 'All Products | PsaOnlineAccessories')

@section('content')
@php
    $categories = [
        'all' => 'All products',
        'jewelry' => 'Jewelry',
        'eyewear' => 'Sunglasses',
        'bags' => 'Bags',
        'hair' => 'Claw clips and pins',
        'charms' => 'Phone charms',
    ];
    $activeCategory = request('category', 'all');
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <!-- Breadcrumb & Header -->
    <div class="mb-6 space-y-2">
        <div class="text-xs text-[#FFA552] font-black flex items-center gap-1.5">
            <a href="{{ route('home') }}" class="hover:underline">Home</a>
            <span>/</span>
            <span class="text-stone-500">Catalog</span>
        </div>
        <h1 class="text-2xl sm:text-4xl font-black text-[#2B1D1D] tracking-tight">
            {{ $activeCategory === 'all' ? 'All products' : ($categories[$activeCategory] ?? 'Products') }}
        </h1>
        <p class="text-xs sm:text-sm text-stone-600">Jewelry, sunglasses, bags, hair clips and phone charms. Prices shown in USD and riel.</p>
    </div>

    <!-- Category Filter Bar -->
    <div class="flex items-center gap-2 overflow-x-auto pb-3 mb-6 text-xs font-bold border-b border-[#EFE4D6]">
        @foreach ($categories as $key => $label)
            <a href="{{ $key === 'all' ? route('products.index') : route('products.index', ['category' => $key]) }}"
               @if ($activeCategory === $key) aria-current="page" @endif
               class="px-4 py-2 rounded-lg shrink-0 {{ $activeCategory === $key ? 'bg-[#2B1D1D] text-white' : 'bg-white text-[#2B1D1D] hover:bg-[#F9F3EA] border border-[#EFE4D6]' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- Products Grid -->
    <div id="catalogGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
        @forelse($products as $product)
            @include('partials.product-card', ['product' => $product])
        @empty
        <div class="col-span-full py-16 text-center text-stone-500 text-sm">
            No products found in this category.
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8 flex justify-center">
        {{ $products->withQueryString()->links() }}
    </div>
</div>
@endsection
