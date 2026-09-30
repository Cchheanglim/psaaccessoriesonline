@extends('layouts.app')

@section('title', 'Sign In — PsaOnline')

@section('content')
<div class="max-w-md mx-auto px-4 py-12 sm:py-16">
    <div class="bg-white rounded-3xl border border-[#E2ECE5] p-6 sm:p-8 shadow-md space-y-6">
        <div class="text-center space-y-1">
            <h1 class="text-2xl font-black text-[#051F20]">Sign In to PsaOnline</h1>
            <p class="text-xs text-slate-500">Track orders and manage delivery details</p>
        </div>

        @if($errors->any())
        <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs">
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-[#051F20] mb-1">Email Address</label>
                <input type="email" name="email" required value="buyer@gmail.com" class="w-full px-3.5 py-2.5 rounded-xl border border-[#E2ECE5] focus:border-[#235347] focus:outline-none" />
            </div>

            <div>
                <label class="block font-bold text-[#051F20] mb-1">Password</label>
                <input type="password" name="password" required value="password123" class="w-full px-3.5 py-2.5 rounded-xl border border-[#E2ECE5] focus:border-[#235347] focus:outline-none" />
            </div>

            <button type="submit" class="btn-press w-full py-3 rounded-xl bg-[#051F20] hover:bg-[#0B2B26] text-white font-extrabold text-xs shadow-md transition-all">
                Sign In
            </button>
        </form>

        <div class="text-center text-xs text-slate-500 pt-2 border-t border-[#E2ECE5]">
            Don't have an account? <a href="{{ route('register') }}" class="font-bold text-[#235347] hover:underline">Create one</a>
        </div>
    </div>
</div>
@endsection
