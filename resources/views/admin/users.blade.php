@extends('layouts.admin')

@section('title', 'Users | PsaOnlineAccessories Admin')

@section('content')
@php
    $roleStyles = [
        'admin' => 'bg-amber-500/20 text-amber-300',
        'staff' => 'bg-sky-500/20 text-sky-300',
        'buyer' => 'bg-emerald-500/20 text-emerald-300',
    ];
    $field = 'w-full px-3 py-2 rounded-lg border border-[#EFE4D6] bg-white text-xs text-[#2B1D1D] focus:border-[#FFA552] focus:outline-none';
@endphp
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-[#2B1D1D]">Users and staff</h1>
        <p class="text-xs text-stone-500">Admins manage everything. Staff can handle orders and products but not users, payment methods or deletions.</p>
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
                        <th class="py-2.5 px-3">Joined</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#163832]">
                    @foreach ($users as $user)
                    <tr>
                        <td class="py-3 px-3 font-bold text-white">{{ $user->name }}</td>
                        <td class="py-3 px-3 text-[#DAF1DE]">{{ $user->email }}</td>
                        <td class="py-3 px-3 text-[#8EB69B]">{{ $user->phone ?: 'None' }}</td>
                        <td class="py-3 px-3"><span class="px-2 py-0.5 rounded-md font-bold text-[10px] {{ $roleStyles[$user->role] ?? $roleStyles['buyer'] }}">{{ ucfirst($user->role) }}</span></td>
                        <td class="py-3 px-3 text-[#8EB69B]">{{ $user->created_at?->format('d M Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div>{{ $users->links() }}</div>

    <!-- Create account -->
    <form action="{{ route('admin.users.store') }}" method="POST" class="bg-white rounded-2xl border border-[#EFE4D6] p-6 space-y-4 text-xs max-w-2xl">
        @csrf
        <h2 class="text-base font-black text-[#2B1D1D]">Add a user</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="new_name" class="block font-bold mb-1">Name *</label>
                <input id="new_name" name="name" required maxlength="100" value="{{ old('name') }}" class="{{ $field }}" />
            </div>
            <div>
                <label for="new_email" class="block font-bold mb-1">Email *</label>
                <input id="new_email" name="email" type="email" required maxlength="255" value="{{ old('email') }}" class="{{ $field }}" />
            </div>
            <div>
                <label for="new_phone" class="block font-bold mb-1">Phone</label>
                <input id="new_phone" name="phone" type="tel" maxlength="30" value="{{ old('phone') }}" class="{{ $field }}" />
            </div>
            <div>
                <label for="new_role" class="block font-bold mb-1">Role *</label>
                <select id="new_role" name="role" required class="{{ $field }}">
                    @foreach (['staff' => 'Staff', 'admin' => 'Admin', 'buyer' => 'Buyer'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', 'staff') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label for="new_password" class="block font-bold mb-1">Temporary password *</label>
                <input id="new_password" name="password" type="password" required autocomplete="new-password" maxlength="255" class="{{ $field }}" />
                <p class="mt-1 text-[11px] text-stone-500">At least {{ app()->isProduction() ? 10 : 8 }} characters with a letter and a number. Share it privately.</p>
            </div>
        </div>
        <div class="flex justify-end">
            <button type="submit" class="px-4 py-2 rounded-lg bg-[#2B1D1D] text-white font-black">Create user</button>
        </div>
    </form>
</div>
@endsection
