@extends('layouts.admin')

@section('title', 'Manage Product Drops — PsaOnline Admin')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-white">Product Inventory Drops</h1>
            <p class="text-xs text-[#8EB69B]">Manage Gen-Z jewelry, shades, bags, and phone charms</p>
        </div>
        <button class="px-4 py-2 rounded-xl bg-[#235347] hover:bg-[#8EB69B] hover:text-[#051F20] text-white font-extrabold text-xs transition">
            + New Accessory Drop
        </button>
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
                        <th class="py-2.5 px-3">Rating</th>
                        <th class="py-2.5 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#163832]">
                    <tr>
                        <td class="py-3 px-3 flex items-center gap-3">
                            <img src="https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=700&q=80" class="w-10 h-10 object-cover rounded-lg border border-[#235347]" />
                            <div>
                                <div class="font-bold text-white">Silver Chrome Star Pendant Necklace</div>
                                <div class="text-[10px] text-[#8EB69B]">Stainless Steel</div>
                            </div>
                        </td>
                        <td class="py-3 px-3 text-[#DAF1DE]">Y2K Jewelry</td>
                        <td class="py-3 px-3 font-bold text-white">$6.50</td>
                        <td class="py-3 px-3 text-emerald-400 font-bold">45 in stock</td>
                        <td class="py-3 px-3 text-amber-400">★ 4.9</td>
                        <td class="py-3 px-3 text-right">
                            <button class="text-xs text-[#8EB69B] hover:text-white font-bold">Edit</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
