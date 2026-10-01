@extends('layouts.admin')

@section('title', 'Dashboard | PsaOnlineAccessories Admin')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-[#2B1D1D]">Overview</h1>
        <p class="text-xs text-stone-500">Orders, payment slips waiting for review, and catalog size.</p>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-[#EFE4D6]">
            <div class="text-xs text-stone-500 font-bold">Confirmed sales (USD)</div>
            <div class="text-2xl font-black text-[#2B1D1D] mt-1">${{ number_format($totalRevenueUsd, 2) }}</div>
        </div>
        <a href="{{ route('admin.orders.index', ['payment_status' => 'slip_uploaded']) }}" class="bg-white p-5 rounded-2xl border border-[#EFE4D6] hover:border-[#FFA552]">
            <div class="text-xs text-stone-500 font-bold">Slips to review</div>
            <div class="text-2xl font-black text-amber-500 mt-1">{{ $slipsToReview }}</div>
        </a>
        <div class="bg-white p-5 rounded-2xl border border-[#EFE4D6]">
            <div class="text-xs text-stone-500 font-bold">Total orders</div>
            <div class="text-2xl font-black text-emerald-600 mt-1">{{ $totalOrders }}</div>
        </div>
        <div class="bg-white p-5 rounded-2xl border border-[#EFE4D6]">
            <div class="text-xs text-stone-500 font-bold">Products on sale</div>
            <div class="text-2xl font-black text-[#FFA552] mt-1">{{ $activeProducts }}</div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="bg-white rounded-2xl border border-[#EFE4D6] p-5 space-y-4">
        <div class="flex items-center justify-between border-b border-[#F9F3EA] pb-3">
            <h2 class="text-sm font-black text-[#2B1D1D]">Recent orders</h2>
            <a href="{{ route('admin.orders.index') }}" class="text-xs text-[#FFA552] hover:underline font-black">View all &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-stone-400 border-b border-[#F9F3EA]">
                        <th class="py-2.5 px-3">Order</th>
                        <th class="py-2.5 px-3">Customer</th>
                        <th class="py-2.5 px-3">Total</th>
                        <th class="py-2.5 px-3">Method</th>
                        <th class="py-2.5 px-3">Payment</th>
                        <th class="py-2.5 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#F9F3EA]">
                    @forelse ($recentOrders as $order)
                    <tr>
                        <td class="py-3 px-3 font-mono font-bold text-[#2B1D1D]">#{{ $order->order_number }}</td>
                        <td class="py-3 px-3 font-bold text-[#2B1D1D]">{{ $order->customer_name }}</td>
                        <td class="py-3 px-3 font-black text-[#2B1D1D]">${{ number_format($order->total_usd, 2) }}</td>
                        <td class="py-3 px-3 text-stone-600">{{ ucwords(str_replace('_', ' ', $order->payment_method)) }}</td>
                        <td class="py-3 px-3">@include('admin.partials.payment-badge', ['status' => $order->payment_status])</td>
                        <td class="py-3 px-3 text-right">
                            <a href="{{ route('admin.orders.review', $order) }}" class="px-3 py-1.5 rounded-lg bg-[#FFA552] hover:bg-[#E88C35] text-white font-black text-[11px] transition">Open</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-stone-500">No orders yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
