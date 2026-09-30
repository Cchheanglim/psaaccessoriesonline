@extends('layouts.app')

@section('title', 'All Viral Drops — PsaOnline Gen-Z Accessories')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <!-- Breadcrumb & Header -->
    <div class="mb-6 space-y-2">
        <div class="text-xs text-[#FFA552] font-black flex items-center gap-1.5">
            <a href="{{ route('home') }}" class="hover:underline">Home</a>
            <span>/</span>
            <span class="text-stone-500">Catalog Drops</span>
        </div>
        <h1 class="text-2xl sm:text-4xl font-black text-[#2B1D1D] tracking-tight">Gen-Z Aesthetic Drops</h1>
        <p class="text-xs sm:text-sm text-stone-600">Discover handpicked jewelry, tinted sunglasses, cloud bags, and aesthetic charms.</p>
    </div>

    <!-- Category Filter Bar -->
    <div class="flex items-center gap-2 overflow-x-auto pb-3 mb-6 text-xs font-bold border-b border-[#EFE4D6]">
        <a href="{{ route('products.index') }}" class="px-4 py-2 rounded-full {{ !request('category') || request('category') == 'all' ? 'bg-[#2B1D1D] text-white shadow-xs' : 'bg-white text-[#2B1D1D] hover:bg-[#F9F3EA] border border-[#EFE4D6]' }}">
            All Drops
        </a>
        <a href="{{ route('products.index', ['category' => 'jewelry']) }}" class="px-4 py-2 rounded-full {{ request('category') == 'jewelry' ? 'bg-[#2B1D1D] text-white shadow-xs' : 'bg-white text-[#2B1D1D] hover:bg-[#F9F3EA] border border-[#EFE4D6]' }}">
            Y2K Jewelry
        </a>
        <a href="{{ route('products.index', ['category' => 'eyewear']) }}" class="px-4 py-2 rounded-full {{ request('category') == 'eyewear' ? 'bg-[#2B1D1D] text-white shadow-xs' : 'bg-white text-[#2B1D1D] hover:bg-[#F9F3EA] border border-[#EFE4D6]' }}">
            Shades & Eyewear
        </a>
        <a href="{{ route('products.index', ['category' => 'bags']) }}" class="px-4 py-2 rounded-full {{ request('category') == 'bags' ? 'bg-[#2B1D1D] text-white shadow-xs' : 'bg-white text-[#2B1D1D] hover:bg-[#F9F3EA] border border-[#EFE4D6]' }}">
            Cloud Bags
        </a>
        <a href="{{ route('products.index', ['category' => 'hair']) }}" class="px-4 py-2 rounded-full {{ request('category') == 'hair' ? 'bg-[#2B1D1D] text-white shadow-xs' : 'bg-white text-[#2B1D1D] hover:bg-[#F9F3EA] border border-[#EFE4D6]' }}">
            Claw Clips & Pins
        </a>
        <a href="{{ route('products.index', ['category' => 'charms']) }}" class="px-4 py-2 rounded-full {{ request('category') == 'charms' ? 'bg-[#2B1D1D] text-white shadow-xs' : 'bg-white text-[#2B1D1D] hover:bg-[#F9F3EA] border border-[#EFE4D6]' }}">
            Phone Charms
        </a>
    </div>

    <!-- Products Grid (Clean Pure White Cards with Cotton Beige Borders) -->
    <div id="catalogGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
        @forelse($products as $product)
        <div class="product-card group flex flex-col justify-between">
            <div class="product-image-container aspect-square p-2.5 flex items-center justify-center">
                <a href="{{ route('products.show', $product->id) }}" class="w-full h-full block overflow-hidden rounded-xl">
                    <img src="{{ $product->image }}" alt="{{ $product->title }}" class="product-image-zoom w-full h-full object-cover" />
                </a>
                @if($product->badge)
                <div class="absolute top-3 left-3 bg-[#FFA552] text-white px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider shadow-xs">
                    {{ $product->badge }}
                </div>
                @endif
                <button onclick="addToCart('{{ $product->id }}', 1)" class="quick-add-btn absolute bottom-3 inset-x-3 py-2 rounded-xl bg-[#FFA552] hover:bg-[#E88C35] text-white font-black text-xs shadow-lg flex items-center justify-center gap-1.5 transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>Quick Add</span>
                </button>
            </div>

            <div class="p-3.5 sm:p-4 flex-1 flex flex-col justify-between space-y-2">
                <div>
                    <div class="flex items-center justify-between text-[11px] text-[#FFA552] font-black">
                        <span>{{ $product->category_label ?? ucfirst($product->category) }}</span>
                        <span class="text-amber-500">★ {{ $product->rating }}</span>
                    </div>
                    <h3 class="text-xs sm:text-sm font-extrabold text-[#2B1D1D] line-clamp-1 mt-1 hover:text-[#FFA552] transition-colors">
                        <a href="{{ route('products.show', $product->id) }}">{{ $product->title }}</a>
                    </h3>
                    @if($product->title_khmer)
                    <p class="text-[11px] text-stone-500 khmer-font line-clamp-1 mt-0.5">{{ $product->title_khmer }}</p>
                    @endif
                </div>

                <div class="pt-2 border-t border-[#F9F3EA] flex items-baseline justify-between">
                    <div>
                        <span class="text-xs sm:text-sm font-black text-[#2B1D1D]">${{ number_format($product->price_usd, 2) }}</span>
                        <span class="text-[10px] text-[#FFA552] block sm:inline sm:ml-1 font-bold">{{ number_format($product->price_khr) }} ៛</span>
                    </div>
                    <a href="{{ route('products.show', $product->id) }}" class="text-[11px] font-bold text-[#FFA552] hover:underline">Details &rarr;</a>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full py-16 text-center text-stone-500 text-sm">
            No accessories found in this collection.
        </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-8 flex justify-center">
        {{ $products->links() }}
    </div>
</div>
@endsection
