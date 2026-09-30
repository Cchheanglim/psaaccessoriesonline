@extends('layouts.admin')

@section('title', 'Manage Staff & Buyers — PsaOnline Admin')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-white">Users & Team Permissions</h1>
            <p class="text-xs text-[#8EB69B]">Manage store administrators, verification staff, and registered buyers</p>
        </div>
    </div>

    <div class="bg-[#0B2B26] rounded-2xl border border-[#235347] p-5">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="text-[#8EB69B] border-b border-[#235347]">
                        <th class="py-2.5 px-3">Name</th>
                        <th class="py-2.5 px-3">Email</th>
                        <th class="py-2.5 px-3">Phone</th>
                        <th class="py-2.5 px-3">Role</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#163832]">
                    <tr>
                        <td class="py-3 px-3 font-bold text-white">Store Administrator</td>
                        <td class="py-3 px-3 text-[#DAF1DE]">admin@psaonline.store</td>
                        <td class="py-3 px-3 text-[#8EB69B]">+855 12 889 900</td>
                        <td class="py-3 px-3"><span class="px-2 py-0.5 rounded-full bg-purple-500/20 text-purple-300 font-bold text-[10px]">Super Admin</span></td>
                    </tr>
                    <tr>
                        <td class="py-3 px-3 font-bold text-white">Sophea Chhum</td>
                        <td class="py-3 px-3 text-[#DAF1DE]">buyer@gmail.com</td>
                        <td class="py-3 px-3 text-[#8EB69B]">+855 96 554 1234</td>
                        <td class="py-3 px-3"><span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 font-bold text-[10px]">Buyer</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
