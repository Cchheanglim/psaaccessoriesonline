@extends('layouts.app')

@section('title', 'Create Account — PsaOnline')

@section('content')
<div class="max-w-md mx-auto px-4 py-12 sm:py-16">
    <div class="bg-white rounded-3xl border border-[#E2ECE5] p-6 sm:p-8 shadow-md space-y-6">
        <div class="text-center space-y-1">
            <h1 class="text-2xl font-black text-[#051F20]">Join PsaOnline</h1>
            <p class="text-xs text-slate-500">Access exclusive viral drops & fast checkout</p>
        </div>

        <form action="{{ route('register') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-[#051F20] mb-1">Full Name</label>
                <input type="text" name="name" required class="w-full px-3.5 py-2.5 rounded-xl border border-[#E2ECE5] focus:border-[#235347] focus:outline-none" />
            </div>

            <div>
                <label class="block font-bold text-[#051F20] mb-1">Email Address</label>
                <input type="email" name="email" required class="w-full px-3.5 py-2.5 rounded-xl border border-[#E2ECE5] focus:border-[#235347] focus:outline-none" />
            </div>

            <div>
                <label class="block font-bold text-[#051F20] mb-1">Password</label>
                <input type="password" name="password" required class="w-full px-3.5 py-2.5 rounded-xl border border-[#E2ECE5] focus:border-[#235347] focus:outline-none" />
            </div>

            <div>
                <label class="block font-bold text-[#051F20] mb-1">Confirm Password</label>
                <input type="password" name="password_confirmation" required class="w-full px-3.5 py-2.5 rounded-xl border border-[#E2ECE5] focus:border-[#235347] focus:outline-none" />
            </div>

            <button type="submit" class="btn-press w-full py-3 rounded-xl bg-[#051F20] hover:bg-[#0B2B26] text-white font-extrabold text-xs shadow-md transition-all">
                Create Account
            </button>
        </form>
    </div>
</div>
@endsection
