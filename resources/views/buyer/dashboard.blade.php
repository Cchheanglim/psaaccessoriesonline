@extends('layouts.app')

@section('title', 'My Account & Orders — PsaOnline')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Buyer Profile Sidebar in Cotton Beige -->
        <div class="lg:col-span-4 bg-white rounded-3xl border border-[#EDEDED] p-6 shadow-sm space-y-4">
            <div class="flex items-center gap-3">
                <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-[#333333] to-[#FF5000] text-white font-black text-xl flex items-center justify-center shadow-md">
                    SC
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-[#333333]">{{ $user->name ?? 'Sophea Chhum' }}</h2>
                    <p class="text-xs text-[#FF5000] font-black">{{ $user->email ?? 'buyer@gmail.com' }}</p>
                </div>
            </div>

            <div class="border-t border-[#FFF3EC] pt-4 space-y-2 text-xs">
                <div class="text-stone-400 font-bold uppercase tracking-wider text-[10px]">Saved Delivery Address</div>
                <p class="text-[#333333] font-medium leading-relaxed">{{ $user->address ?? 'Toul Kork, St 315, House #14, Phnom Penh' }}</p>
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
            <div class="bg-white rounded-3xl border border-[#EDEDED] p-6 shadow-sm">
                <h3 class="text-base font-extrabold text-[#333333] mb-4">Recent Accessory Orders</h3>

                <div class="space-y-4">
                    @forelse($orders as $ord)
                    <div class="p-4 rounded-2xl border border-[#EDEDED] hover:border-[#FF5000] transition flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <div class="text-xs font-black text-[#333333]">Order #{{ $ord->order_number }}</div>
                            <div class="text-[11px] text-stone-500">{{ $ord->created_at->format('M d, Y') }} &bull; {{ $ord->items->count() }} items</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-2.5 py-1 rounded-full bg-[#FFF3EC] text-[#FF5000] border border-[#FF5000] text-[10px] font-black uppercase">
                                {{ ucwords(str_replace('_', ' ', $ord->order_status)) }}
                            </span>
                            <a href="{{ route('orders.show', $ord->id) }}" class="px-3.5 py-1.5 rounded-xl bg-[#FF5000] hover:bg-[#E64500] text-white text-xs font-black">
                                View Receipt
                            </a>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8 text-xs text-stone-500">
                        No orders placed yet.
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
