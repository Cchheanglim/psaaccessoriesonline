@extends('layouts.admin')

@section('title', 'Review Payment Slip: Order #PSA-902184 — PsaOnline')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('admin.orders.index') }}" class="text-xs text-[#8EB69B] hover:text-white font-bold">&larr; Back to Orders</a>
            <h1 class="text-2xl font-black text-white mt-1">Review KHQR Payment Slip</h1>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
        <!-- Uploaded Slip Preview -->
        <div class="bg-[#0B2B26] p-6 rounded-2xl border border-[#235347] space-y-4 text-center">
            <h2 class="text-xs font-bold text-[#8EB69B] uppercase tracking-wider">Customer Transfer Slip</h2>
            <div class="bg-white p-2 rounded-xl border border-slate-300 max-w-sm mx-auto shadow-inner">
                <img
                    src="https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=600&q=80"
                    alt="Bakong Slip Proof"
                    class="w-full h-80 object-cover rounded-lg"
                />
            </div>
            <div class="text-xs text-[#DAF1DE]">
                <span>Transaction Ref: <strong>FT2409301829381</strong></span>
            </div>
        </div>

        <!-- Verification Actions -->
        <div class="bg-[#0B2B26] p-6 rounded-2xl border border-[#235347] space-y-5">
            <h2 class="text-base font-extrabold text-white border-b border-[#235347] pb-3">Order Details</h2>
            
            <div class="space-y-2 text-xs">
                <div class="flex justify-between"><span class="text-[#8EB69B]">Order Number:</span> <span class="font-mono font-bold text-white">#PSA-902184</span></div>
                <div class="flex justify-between"><span class="text-[#8EB69B]">Buyer:</span> <span class="font-bold text-white">Sophea Chhum</span></div>
                <div class="flex justify-between"><span class="text-[#8EB69B]">Telegram / Phone:</span> <span class="font-bold text-white">+855 96 554 1234</span></div>
                <div class="flex justify-between"><span class="text-[#8EB69B]">Total Expected:</span> <span class="font-black text-emerald-400 text-sm">$18.50 (75,850 ៛)</span></div>
                <div class="flex justify-between"><span class="text-[#8EB69B]">Delivery:</span> <span class="text-white">Toul Kork, St 315, House #14, Phnom Penh</span></div>
            </div>

            <div class="pt-4 border-t border-[#235347] space-y-3">
                <form action="{{ route('admin.orders.verify', 1) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-3 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-extrabold text-xs shadow-md transition-all flex items-center justify-center gap-2">
                        <span>✓ Approve & Verify Payment</span>
                    </button>
                </form>

                <button type="button" class="w-full py-2.5 rounded-xl border border-red-500/50 text-red-400 hover:bg-red-500/10 text-xs font-bold transition">
                    Reject Slip (Invalid Amount or Image)
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
