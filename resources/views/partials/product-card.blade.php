<div class="product-card group flex flex-col justify-between" data-category="{{ $product->category }}">
    <div class="product-image-container aspect-square p-2.5 flex items-center justify-center">
        <a href="{{ route('products.show', $product->id) }}" class="w-full h-full block overflow-hidden rounded-xl">
            <img src="{{ $product->image }}" alt="{{ $product->title }}" loading="lazy" class="product-image-zoom w-full h-full object-cover" />
        </a>
        @if($product->badge)
        <div class="absolute top-3 left-3 bg-[#FFA552] text-white px-2 py-1 rounded-md text-[10px] font-black uppercase tracking-wider">
            {{ $product->badge }}
        </div>
        @endif
        <button type="button" onclick="addToCart('{{ $product->id }}', 1)" class="quick-add-btn absolute bottom-3 inset-x-3 py-2 rounded-xl bg-[#FFA552] hover:bg-[#E88C35] text-white font-black text-xs shadow-lg flex items-center justify-center gap-1.5 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
            <span>Add to bag</span>
        </button>
    </div>

    <div class="p-3.5 sm:p-4 flex-1 flex flex-col justify-between space-y-2">
        <div>
            <div class="text-[11px] text-[#FFA552] font-black">
                {{ $product->category_label ?? ucfirst($product->category) }}
            </div>
            <h3 class="text-xs sm:text-sm font-extrabold text-[#2B1D1D] line-clamp-1 mt-1 hover:text-[#FFA552] transition-colors">
                <a href="{{ route('products.show', $product->id) }}">{{ $product->title }}</a>
            </h3>
            @if($product->title_khmer)
            <p class="text-[11px] text-stone-500 font-khmer line-clamp-1 mt-0.5">{{ $product->title_khmer }}</p>
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
