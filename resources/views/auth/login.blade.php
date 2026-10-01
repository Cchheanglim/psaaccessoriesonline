@extends('layouts.app')

@section('title', 'Sign In | PsaOnlineAccessories')

@section('content')
<div class="max-w-md mx-auto px-4 py-12 sm:py-16">
    <div class="bg-white rounded-3xl border border-[#E2ECE5] p-6 sm:p-8 shadow-md space-y-6">
        <div class="text-center space-y-1">
            <h1 class="text-2xl font-black text-[#051F20]">Sign in</h1>
            <p class="text-xs text-slate-500">See your orders and saved delivery details.</p>
        </div>

        @if($errors->any())
        <div role="alert" class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs">
            {{ $errors->first() }}
        </div>
        @endif

        <form action="{{ route('login') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div>
                <label for="email" class="block font-bold text-[#051F20] mb-1">Email address</label>
                <input id="email" type="email" name="email" required autofocus autocomplete="email" maxlength="255" value="{{ old('email') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-[#E2ECE5] focus:border-[#235347] focus:outline-none" />
            </div>

            <div>
                <label for="password" class="block font-bold text-[#051F20] mb-1">Password</label>
                <input id="password" type="password" name="password" required autocomplete="current-password" maxlength="255" class="w-full px-3.5 py-2.5 rounded-xl border border-[#E2ECE5] focus:border-[#235347] focus:outline-none" />
            </div>

            <label class="flex items-center gap-2 text-slate-600">
                <input type="checkbox" name="remember" value="1" class="rounded border-[#E2ECE5]" />
                <span>Keep me signed in on this device</span>
            </label>

            <button type="submit" class="btn-press w-full py-3 rounded-xl bg-[#051F20] hover:bg-[#0B2B26] text-white font-extrabold text-xs shadow-md transition-all">
                Sign in
            </button>
        </form>

        <div class="text-center text-xs text-slate-500 pt-2 border-t border-[#E2ECE5]">
            No account yet? <a href="{{ route('register') }}" class="font-bold text-[#235347] hover:underline">Create one</a>
        </div>
    </div>
</div>
@endsection
