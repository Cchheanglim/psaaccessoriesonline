@extends('layouts.admin')

@section('title', 'Admin Operations Portal — PsaOnline')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-[#2B1D1D]">Operations Overview</h1>
            <p class="text-xs text-stone-500">Real-time orders, payment slips verification, and inventory status</p>
        </div>
        <div class="text-xs font-black bg-[#F9F3EA] text-[#FFA552] border border-[#FFA552] px-3 py-1.5 rounded-full">
            Phnom Penh Hub Active 🟢
        </div>
    </div>

    <!-- Stat KPI Cards in Cotton Beige & Sorbet -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-[#EFE4D6]">
            <div class="text-xs text-stone-500 font-bold">Total Sales (USD)</div>
            <div class="text-2xl font-black text-[#2B1D1D] mt-1">${{ number_format($totalRevenueUsd ?? 482.50, 2) }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-[#EFE4D6]">
            <div class="text-xs text-stone-500 font-bold">Pending Payment Slips</div>
            <div class="text-2xl font-black text-amber-500 mt-1">{{ $pendingPayments ?? 3 }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-[#EFE4D6]">
            <div class="text-xs text-stone-500 font-bold">Completed Orders</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ $totalOrders ?? 28 }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-[#EFE4D6]">
            <div class="text-xs text-stone-500 font-bold">Active Accessories</div>
            <div class="text-2xl font-black text-[#FFA552] mt-1">{{ $totalProducts ?? 12 }}</div>
        </div>
    </div>

    <!-- Recent Orders Table -->
    <div class="bg-white rounded-2xl border border-[#EFE4D6] p-5 space-y-4">
        <div class="flex items-center justify-between border-b border-[#F9F3EA] pb-3">
            <h2 class="text-sm font-black text-[#2B1D1D]">Recent Customer Orders</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-xs text-[#FFA552] hover:underline font-black">View All &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-stone-400 border-b border-[#F9F3EA]">
                        <th class="py-2.5 px-3">Order ID</th>
                        <th class="py-2.5 px-3">Customer</th>
                        <th class="py-2.5 px-3">Total</th>
                        <th class="py-2.5 px-3">Gateway</th>
                        <th class="py-2.5 px-3">Payment</th>
                        <th class="py-2.5 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F9F3EA]">
                    <tr>
                        <td class="py-3 px-3 font-mono font-bold text-[#2B1D1D]">#PSA-902184</td>
                        <td class="py-3 px-3 font-bold text-[#2B1D1D]">Sophea Chhum</td>
                        <td class="py-3 px-3 font-black text-[#2B1D1D]">$18.50</td>
                        <td class="py-3 px-3 text-stone-600">Bakong KHQR</td>
                        <td class="py-3 px-3"><span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-black text-[10px] border border-amber-200">Slip Uploaded</span></td>
                        <td class="py-3 px-3 text-right">
                            <a href="{{ route('admin.orders.review', 1) }}" class="px-3 py-1.5 rounded-xl bg-[#FFA552] hover:bg-[#E88C35] text-white font-black text-[11px] transition">Review Slip</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
