@extends('layouts.app')

@section('title', 'My Account | PsaOnlineAccessories')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Buyer Profile Sidebar in Cotton Beige -->
        <div class="lg:col-span-4 bg-white rounded-3xl border border-[#EFE4D6] p-6 shadow-xs space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-14 h-14 rounded-2xl bg-[#2B1D1D] text-[#FFA552] font-black text-xl flex items-center justify-center shadow-md">
                    {{ $user->initials() }}
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-[#2B1D1D]">{{ $user->name }}</h2>
                    <p class="text-xs text-[#FFA552] font-black">{{ $user->email }}</p>
                </div>
            </div>

            <div class="border-t border-[#F9F3EA] pt-4 space-y-2 text-xs">
                <div class="text-stone-400 font-bold uppercase tracking-wider text-[10px]">Saved Delivery Address</div>
                <p class="text-[#2B1D1D] font-medium leading-relaxed">{{ $user->address ?: 'No saved address yet. You can enter one at checkout.' }}</p>
            </div>

            <div class="pt-2">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2.5 rounded-xl border border-red-200 text-red-600 hover:bg-red-50 text-xs font-bold transition">
                        Sign Out
                    </button>
                </form>
            </div>
        </div>

        <!-- Orders History -->
        <div class="lg:col-span-8 space-y-6">
            <div class="bg-white rounded-3xl border border-[#EFE4D6] p-6 shadow-xs">
                <h3 class="text-base font-extrabold text-[#2B1D1D] mb-4">Your orders</h3>

                <div class="space-y-4">
                    @forelse($orders as $ord)
                    <div class="p-4 rounded-2xl border border-[#EFE4D6] hover:border-[#FFA552] transition flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <div class="text-xs font-black text-[#2B1D1D]">Order #{{ $ord->order_number }}</div>
                            <div class="text-[11px] text-stone-500">{{ $ord->created_at->format('M d, Y') }} &bull; {{ $ord->items->sum('quantity') }} {{ Str::plural('item', $ord->items->sum('quantity')) }} &bull; ${{ number_format($ord->total_usd, 2) }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-1 rounded-md bg-[#F9F3EA] text-[#FFA552] border border-[#FFA552] text-[10px] font-black uppercase">
                                {{ ucwords(str_replace('_', ' ', $ord->order_status)) }}
                            </span>
                            <a href="{{ route('orders.show', $ord->id) }}" class="px-3.5 py-1.5 rounded-xl bg-[#FFA552] hover:bg-[#E88C35] text-white text-xs font-black">
                                View order
                            </a>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 text-xs text-stone-500">
                        You have not placed any orders yet. <a href="{{ route('products.index') }}" class="font-bold text-[#FFA552] underline">Browse products</a>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
