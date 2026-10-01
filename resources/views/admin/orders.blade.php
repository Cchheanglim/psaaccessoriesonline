@extends('layouts.admin')

@section('title', 'Orders | PsaOnlineAccessories Admin')

@section('content')
@php
    $paymentFilters = [
        '' => 'All',
        'slip_uploaded' => 'Slip uploaded',
        'pending_slip' => 'Awaiting slip',
        'pending' => 'Cash on delivery',
        'verified' => 'Verified',
        'failed' => 'Failed',
    ];
    $activeFilter = request('payment_status', '');
@endphp
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-[#2B1D1D]">Orders</h1>
        <p class="text-xs text-stone-500">Check transfer slips and update delivery status.</p>
    </div>

    <div class="flex items-center gap-2 overflow-x-auto text-xs font-bold">
        @foreach ($paymentFilters as $value => $label)
            <a href="{{ $value === '' ? route('admin.orders.index') : route('admin.orders.index', ['payment_status' => $value]) }}"
               class="shrink-0 px-3 py-1.5 rounded-lg {{ $activeFilter === $value ? 'bg-[#2B1D1D] text-white' : 'bg-white border border-[#EFE4D6] text-stone-600 hover:text-[#2B1D1D]' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="bg-[#0B2B26] rounded-2xl border border-[#235347] p-5">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-[#8EB69B] border-b border-[#235347]">
                        <th class="py-2.5 px-3">Order</th>
                        <th class="py-2.5 px-3">Customer</th>
                        <th class="py-2.5 px-3">Address</th>
                        <th class="py-2.5 px-3">Total (USD / KHR)</th>
                        <th class="py-2.5 px-3">Payment</th>
                        <th class="py-2.5 px-3">Status</th>
                        <th class="py-2.5 px-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#163832]">
                    @forelse ($orders as $order)
                    <tr>
                        <td class="py-3 px-3">
                            <div class="font-mono font-bold text-white">#{{ $order->order_number }}</div>
                            <div class="text-[11px] text-[#8EB69B]">{{ $order->created_at->format('d M Y, H:i') }}</div>
                        </td>
                        <td class="py-3 px-3">
                            <div class="font-bold text-white">{{ $order->customer_name }}</div>
                            <div class="text-[11px] text-[#8EB69B]">{{ $order->customer_phone }}</div>
                        </td>
                        <td class="py-3 px-3 text-[#DAF1DE] max-w-xs truncate" title="{{ $order->delivery_address }}">{{ $order->delivery_address }}</td>
                        <td class="py-3 px-3 font-bold text-white">${{ number_format($order->total_usd, 2) }} <span class="text-[10px] text-[#8EB69B]">({{ number_format($order->total_khr) }} ៛)</span></td>
                        <td class="py-3 px-3">@include('admin.partials.payment-badge', ['status' => $order->payment_status])</td>
                        <td class="py-3 px-3 text-[#DAF1DE]">{{ ucwords(str_replace('_', ' ', $order->order_status)) }}</td>
                        <td class="py-3 px-3 text-right">
                            <a href="{{ route('admin.orders.review', $order) }}" class="px-2.5 py-1 rounded-md bg-[#235347] hover:bg-[#8EB69B] hover:text-[#051F20] text-white font-bold text-[11px] transition">
                                {{ $order->payment_status === 'slip_uploaded' ? 'Review slip' : 'Open' }}
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-[#8EB69B]">No orders match this filter.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $orders->links() }}</div>
</div>
@endsection
