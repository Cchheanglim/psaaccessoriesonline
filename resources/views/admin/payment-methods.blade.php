@extends('layouts.admin')

@section('title', 'Payment Gateway Settings — PsaOnline Admin')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-white">Bakong KHQR & Payments Config</h1>
            <p class="text-xs text-[#8EB69B]">Configure Cambodian bank integrations, merchant IDs, and dual currency rates</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-[#0B2B26] p-6 rounded-2xl border border-[#235347] space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-extrabold text-white">Bakong Universal KHQR</h2>
                <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold text-[10px]">Active</span>
            </div>
            <p class="text-xs text-slate-300">National Bank of Cambodia Universal KHQR standard for peer-to-peer and merchant payments.</p>
            
            <div class="space-y-2 text-xs">
                <div><span class="text-[#8EB69B]">Merchant ID:</span> <span class="font-mono text-white">psaonline@aclb</span></div>
                <div><span class="text-[#8EB69B]">Account Holder:</span> <span class="text-white">PSA ONLINE STORE</span></div>
                <div><span class="text-[#8EB69B]">Fixed Exchange Rate:</span> <span class="text-emerald-400 font-bold">1 USD = 4,100 KHR</span></div>
            </div>
        </div>

        <div class="bg-[#0B2B26] p-6 rounded-2xl border border-[#235347] space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-extrabold text-white">ABA PAY Direct</h2>
                <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold text-[10px]">Active</span>
            </div>
            <p class="text-xs text-slate-300">Instant deep-linking for ABA Mobile app users with automatic push confirmation.</p>
            
            <div class="space-y-2 text-xs">
                <div><span class="text-[#8EB69B]">ABA Account:</span> <span class="font-mono text-white">001 234 567 (USD)</span></div>
                <div><span class="text-[#8EB69B]">Status:</span> <span class="text-emerald-400 font-bold">Verified Merchant</span></div>
            </div>
        </div>
    </div>
</div>
@endsection
