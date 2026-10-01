@extends('layouts.app')

@section('title', ($order->payment_method === 'acleda_khqr' ? 'Pending ACLEDA Payment' : 'Pending Payment: Scan Bakong KHQR') . ' — PsaOnline')

@section('content')
@php($isAcleda = $order->payment_method === 'acleda_khqr')
<style>
    .pending-payment-dark { color: #e8ebf0; }
    .pending-payment-dark .pending-payment-card { background: #1e2430; border-color: #f59e0b; color: #f4f5f7; }
    .pending-payment-dark .pending-payment-muted { color: #aeb6c4; }
</style>
<div class="pending-payment-dark max-w-3xl mx-auto px-4 sm:px-6 py-8 sm:py-12">
    <div class="pending-payment-card rounded-3xl border-2 border-[#FFA552] p-6 sm:p-8 shadow-xl text-center space-y-6">
        
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[#F9F3EA] text-[#FFA552] text-xs font-black border border-[#FFA552]">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
            <span>{{ $isAcleda ? 'Awaiting ACLEDA Payment' : 'Awaiting Payment Verification' }}</span>
        </div>

        <div>
            <h1 class="text-2xl sm:text-3xl font-black text-white">{{ $isAcleda ? 'Pay with ACLEDA Mobile' : 'Scan Bakong Universal KHQR' }}</h1>
            <p class="text-xs sm:text-sm text-stone-500 mt-1">Order #{{ $order->order_number }} &bull; Total: ${{ number_format($order->total_usd, 2) }} ({{ number_format($order->total_khr) }} ៛)</p>
        </div>

        <!-- Universal KHQR Frame in Pure White with Sorbet Orange details -->
        <div class="inline-block p-4 sm:p-6 rounded-3xl bg-white border-2 border-[#FFA552] shadow-lg mx-auto">
            <div class="bg-red-600 text-white font-black text-xs py-1 px-4 rounded-full mb-3 uppercase tracking-wider inline-block">
                {{ $isAcleda ? 'ACLEDA KHQR • Demo' : 'KHQR Universal' }}
            </div>
            
            <div class="w-56 h-56 sm:w-64 sm:h-64 mx-auto bg-white border border-[#EFE4D6] rounded-2xl p-2.5 flex items-center justify-center">
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=260x260&data={{ urlencode($isAcleda ? 'DEMO|ACLEDA|' . $order->order_number . '|USD:' . number_format($order->total_usd, 2, '.', '') . '|KHR:' . $order->total_khr : 'bakong_khqr_psaonline_' . $order->order_number) }}"
                    alt="{{ $isAcleda ? 'Demonstration ACLEDA KHQR' : 'Bakong KHQR Code' }}"
                    class="w-full h-full object-contain"
                />
            </div>

            <div class="mt-3 text-xs font-black text-[#2B1D1D]">
                <div class="text-white">PSA ONLINE STORE</div>
                <div class="text-[11px] text-[#FFA552]">{{ $isAcleda ? 'ACLEDA ToanChet' : 'psaonline@aclb' }}</div>
            </div>
        </div>

        @if ($isAcleda)
            <div class="max-w-md mx-auto space-y-3 rounded-2xl border border-slate-700 bg-[#151922] p-4 text-left">
                <p class="text-[11px] text-amber-300">Demo QR only. A live Bakong/ACLEDA merchant connection is required to accept payment.</p>
                <a href="acledamobile://pay?order={{ urlencode($order->order_number) }}&amp;amount={{ number_format($order->total_usd, 2, '.', '') }}&amp;currency=USD" class="block rounded-xl bg-amber-500 px-4 py-3 text-center text-xs font-black text-slate-950">Open in ACLEDA ToanChet App</a>
                <div class="flex items-center justify-between gap-3 text-xs"><span id="acledaStatus" class="text-amber-300">Automatic status check • Awaiting provider</span><strong id="acledaTimer" class="font-mono text-white">15:00</strong></div>
                <button type="button" id="simulateAcledaPayment" class="w-full rounded-xl border border-slate-600 px-3 py-2.5 text-xs font-bold text-slate-200 hover:border-amber-400">Simulate bank confirmation</button>
            </div>
            <script>
                (() => {
                    let secondsLeft = 15 * 60;
                    const timer = document.getElementById('acledaTimer');
                    const status = document.getElementById('acledaStatus');
                    const interval = setInterval(() => {
                        secondsLeft -= 1;
                        timer.textContent = `${String(Math.floor(secondsLeft / 60)).padStart(2, '0')}:${String(secondsLeft % 60).padStart(2, '0')}`;
                        if (secondsLeft <= 0) {
                            clearInterval(interval);
                            status.textContent = 'Payment request expired';
                            status.className = 'text-rose-300';
                        }
                    }, 1000);
                    document.getElementById('simulateAcledaPayment').addEventListener('click', event => {
                        clearInterval(interval);
                        timer.textContent = '00:00';
                        status.textContent = 'Demo payment confirmed';
                        status.className = 'text-emerald-300';
                        event.currentTarget.hidden = true;
                    });
                })();
            </script>
        @endif

        <!-- Payment Slip Upload Form -->
        <form action="{{ route('orders.upload-slip', $order->id) }}" method="POST" enctype="multipart/form-data" class="max-w-md mx-auto space-y-4 pt-4 border-t border-[#EFE4D6] text-left">
            @csrf
            <div>
                <label class="block text-xs font-bold text-[#2B1D1D] mb-1.5">
                    Upload Bank Transfer Slip / Screenshot *
                </label>
                <input
                    type="file"
                    name="payment_slip"
                    required
                    accept="image/*"
                    class="w-full text-xs text-stone-600 file:mr-3 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-[#FFA552] file:text-white hover:file:bg-[#E88C35] file:cursor-pointer"
                />
            </div>

            <button
                type="submit"
                class="btn-press w-full py-3.5 rounded-2xl bg-[#FFA552] hover:bg-[#E88C35] text-white font-black text-xs shadow-md transition-all flex items-center justify-center gap-2"
            >
                <span>Submit Slip for Verification</span>
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
            </button>
        </form>

        <div class="text-xs pending-payment-muted">
            Need urgent assistance? Reach our studio on Telegram: <a href="https://t.me/psaonline_support" class="text-[#FFA552] font-black underline">@psaonline_support</a>
        </div>
    </div>
</div>
@endsection
