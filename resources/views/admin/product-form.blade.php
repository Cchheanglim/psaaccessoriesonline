@extends('layouts.admin')

@section('title', ($product->exists ? 'Edit product' : 'New product') . ' | PsaOnlineAccessories Admin')

@section('content')
@php
    $categories = ['jewelry' => 'Jewelry', 'eyewear' => 'Sunglasses', 'bags' => 'Bags', 'hair' => 'Hair clips and pins', 'charms' => 'Phone charms'];
    $field = 'w-full px-3 py-2 rounded-lg border border-[#EFE4D6] bg-white text-xs text-[#2B1D1D] focus:border-[#FFA552] focus:outline-none';
@endphp
<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="{{ route('admin.products.index') }}" class="text-xs text-stone-500 hover:text-[#2B1D1D] font-bold">&larr; Back to products</a>
        <h1 class="text-2xl font-black text-[#2B1D1D] mt-1">{{ $product->exists ? 'Edit product' : 'New product' }}</h1>
    </div>

    <form action="{{ $product->exists ? route('admin.products.update', $product) : route('admin.products.store') }}" method="POST" class="bg-white rounded-2xl border border-[#EFE4D6] p-6 space-y-4 text-xs">
        @csrf
        @if ($product->exists)
            @method('PUT')
        @endif

        <div>
            <label for="title" class="block font-bold mb-1">Title *</label>
            <input id="title" name="title" required maxlength="200" value="{{ old('title', $product->title) }}" class="{{ $field }}" />
        </div>
        <div>
            <label for="title_khmer" class="block font-bold mb-1">Title in Khmer</label>
            <input id="title_khmer" name="title_khmer" maxlength="200" value="{{ old('title_khmer', $product->title_khmer) }}" class="{{ $field }} font-khmer" />
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="category" class="block font-bold mb-1">Category *</label>
                <select id="category" name="category" required class="{{ $field }}">
                    @foreach ($categories as $value => $label)
                        <option value="{{ $value }}" @selected(old('category', $product->category) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="price_usd" class="block font-bold mb-1">Price (USD) *</label>
                <input id="price_usd" name="price_usd" type="number" required min="0" max="100000" step="0.01" value="{{ old('price_usd', $product->price_usd) }}" class="{{ $field }}" />
            </div>
            <div>
                <label for="stock" class="block font-bold mb-1">Stock *</label>
                <input id="stock" name="stock" type="number" required min="0" step="1" value="{{ old('stock', $product->stock ?? 0) }}" class="{{ $field }}" />
            </div>
        </div>
        <div>
            <label for="image" class="block font-bold mb-1">Image URL</label>
            <input id="image" name="image" type="url" maxlength="2048" placeholder="https://" value="{{ old('image', $product->image) }}" class="{{ $field }}" />
            <p class="mt-1 text-[11px] text-stone-500">Use a photo of the actual product.</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="material" class="block font-bold mb-1">Material</label>
                <input id="material" name="material" maxlength="100" value="{{ old('material', $product->material) }}" class="{{ $field }}" />
            </div>
            <div>
                <label for="badge" class="block font-bold mb-1">Label</label>
                <input id="badge" name="badge" maxlength="50" placeholder="For example: New" value="{{ old('badge', $product->badge) }}" class="{{ $field }}" />
            </div>
        </div>
        <div>
            <label for="description" class="block font-bold mb-1">Description</label>
            <textarea id="description" name="description" rows="4" maxlength="5000" class="{{ $field }}">{{ old('description', $product->description) }}</textarea>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('admin.products.index') }}" class="px-4 py-2 rounded-lg border border-[#EFE4D6] font-bold text-stone-600">Cancel</a>
            <button type="submit" class="px-4 py-2 rounded-lg bg-[#2B1D1D] text-white font-black">{{ $product->exists ? 'Save changes' : 'Add product' }}</button>
        </div>
    </form>
</div>
@endsection
