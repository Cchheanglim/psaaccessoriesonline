@extends('layouts.admin')

@section('title', 'Manage Orders & KHQR Slips — PsaOnline Admin')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-white">Order Fulfillment & Slips</h1>
            <p class="text-xs text-[#8EB69B]">Verify Bakong KHQR bank slips and dispatch couriers</p>
        </div>
    </div>

    <!-- Orders Filter Container -->
    <div class="bg-[#0B2B26] rounded-2xl border border-[#235347] p-5">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-[#8EB69B] border-b border-[#235347]">
                        <th class="py-2.5 px-3">Order Number</th>
                        <th class="py-2.5 px-3">Buyer & Phone</th>
                        <th class="py-2.5 px-3">Address</th>
                        <th class="py-2.5 px-3">Total (USD/KHR)</th>
                        <th class="py-2.5 px-3">Slip Status</th>
                        <th class="py-2.5 px-3">Courier</th>
                        <th class="py-2.5 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#163832]">
                    <tr>
                        <td class="py-3 px-3 font-mono font-bold text-white">#PSA-902184</td>
                        <td class="py-3 px-3">
                            <div class="font-bold text-white">Sophea Chhum</div>
                            <div class="text-[11px] text-[#8EB69B]">+855 96 554 1234</div>
                        </td>
                        <td class="py-3 px-3 text-[#DAF1DE] max-w-xs truncate">Toul Kork, St 315, House #14, Phnom Penh</td>
                        <td class="py-3 px-3 font-bold text-white">$18.50 <span class="text-[10px] text-[#8EB69B]">(75,850 ៛)</span></td>
                        <td class="py-3 px-3">
                            <span class="px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 font-bold text-[10px]">Slip Uploaded</span>
                        </td>
                        <td class="py-3 px-3">
                            <span class="px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-300 font-bold text-[10px]">Pending Verification</span>
                        </td>
                        <td class="py-3 px-3 text-right space-x-2">
                            <a href="{{ route('admin.orders.review', 1) }}" class="px-2.5 py-1 rounded bg-[#235347] hover:bg-[#8EB69B] hover:text-[#051F20] text-white font-bold text-[11px] transition">Review Slip</a>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
