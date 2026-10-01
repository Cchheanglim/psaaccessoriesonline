@extends('layouts.admin')

@section('title', 'Products | PsaOnlineAccessories Admin')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-[#2B1D1D]">Products</h1>
            <p class="text-xs text-stone-500">Jewelry, sunglasses, bags, hair clips and phone charms.</p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="px-4 py-2 rounded-xl bg-[#235347] hover:bg-[#8EB69B] hover:text-[#051F20] text-white font-extrabold text-xs transition">
            + New product
        </a>
    </div>

    <div class="bg-[#0B2B26] rounded-2xl border border-[#235347] p-5">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-[#8EB69B] border-b border-[#235347]">
                        <th class="py-2.5 px-3">Item</th>
                        <th class="py-2.5 px-3">Category</th>
                        <th class="py-2.5 px-3">Price (USD)</th>
                        <th class="py-2.5 px-3">Stock</th>
                        <th class="py-2.5 px-3">Status</th>
                        <th class="py-2.5 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#163832]">
                    @forelse ($products as $product)
                    <tr>
                        <td class="py-3 px-3">
                            <div class="flex items-center gap-3">
                                @if ($product->image)
                                    <img src="{{ $product->image }}" alt="" class="w-10 h-10 object-cover rounded-lg border border-[#235347]" />
                                @else
                                    <div class="w-10 h-10 rounded-lg border border-[#235347] bg-[#051F20]"></div>
                                @endif
                                <div>
                                    <div class="font-bold text-white">{{ $product->title }}</div>
                                    @if ($product->material)
                                        <div class="text-[10px] text-[#8EB69B]">{{ $product->material }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-3 text-[#DAF1DE]">{{ $product->category_label ?? ucfirst($product->category) }}</td>
                        <td class="py-3 px-3 font-bold text-white">${{ number_format($product->price_usd, 2) }}</td>
                        <td class="py-3 px-3 font-bold {{ $product->stock > 0 ? 'text-emerald-400' : 'text-red-400' }}">{{ $product->stock > 0 ? $product->stock . ' in stock' : 'Out of stock' }}</td>
                        <td class="py-3 px-3 text-[#DAF1DE]">{{ ucfirst($product->status) }}</td>
                        <td class="py-3 px-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.products.edit', $product) }}" class="text-xs text-[#8EB69B] hover:text-white font-bold">Edit</a>
                            @if (auth()->user()->isAdmin())
                                <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline ml-3" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-400 hover:text-red-300 font-bold">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-[#8EB69B]">No products yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $products->links() }}</div>
</div>
@endsection
