@extends('layouts.app')

@section('title', 'Pending Payment: Scan Bakong KHQR — PsaOnline')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
    <div class="bg-white rounded-3xl border-2 border-[#FF5000] p-6 sm:p-8 shadow-xl text-center space-y-6">
        
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#FFF3EC] text-[#FF5000] text-xs font-black border border-[#FF5000]">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
            <span>Awaiting Payment Verification</span>
        </div>

        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-[#333333]">Scan Bakong Universal KHQR</h1>
            <p class="text-xs sm:text-sm text-stone-500 mt-1">Order #{{ $order->order_number }} &bull; Total: ${{ number_format($order->total_usd, 2) }} ({{ number_format($order->total_khr) }} ៛)</p>
        </div>

        <!-- Universal KHQR Frame in Pure White with Sorbet Orange details -->
        <div class="inline-block p-4 sm:p-6 rounded-3xl bg-white border-2 border-[#FF5000] shadow-lg mx-auto">
            <div class="bg-red-600 text-white font-black text-xs py-1 px-4 rounded-full mb-3 uppercase tracking-wider inline-block">
                KHQR Universal
            </div>
            
            <div class="w-56 h-56 sm:w-64 sm:h-64 mx-auto bg-white border border-[#EDEDED] rounded-2xl p-2.5 flex items-center justify-center">
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=bakong_khqr_psaonline_{{ $order->order_number }}"
                    alt="Bakong KHQR Code"
                    class="w-full h-full object-contain"
                />
            </div>

            <div class="mt-3 text-xs font-black text-[#333333]">
                <div>PSA ONLINE STORE</div>
                <div class="text-[11px] text-[#FF5000]">psaonline@aclb</div>
            </div>
        </div>

        <!-- Payment Slip Upload Form -->
        <form action="{{ route('orders.upload-slip', $order->id) }}" method="POST" enctype="multipart/form-data" class="max-w-md mx-auto space-y-4 pt-4 border-t border-[#EDEDED] text-left">
            @csrf
            <div>
                <label class="block text-xs font-bold text-[#333333] mb-1.5">
                    Upload Bank Transfer Slip / Screenshot *
                </label>
                <input
                    type="file"
                    name="payment_slip"
                    required
                    accept="image/*"
                    class="w-full text-xs text-stone-600 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-[#FF5000] file:text-white hover:file:bg-[#E64500] file:cursor-pointer"
                />
            </div>

            <button
                type="submit"
                class="btn-press w-full py-3.5 rounded-2xl bg-[#FF5000] hover:bg-[#E64500] text-white font-black text-xs shadow-md transition-all flex items-center justify-center gap-2"
            >
                <span>Submit Slip for Verification</span>
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
            </button>
        </form>

        <div class="text-xs text-stone-400">
            Need urgent assistance? Reach our studio on Telegram: <a href="https://t.me/psaonline_support" class="text-[#FF5000] font-black underline">@psaonline_support</a>
        </div>
    </div>
</div>
@endsection
