@extends('layouts.app')

@section('title', 'Create Account | PsaOnlineAccessories')

@section('content')
@php($minLength = app()->isProduction() ? 10 : 8)
<div class="max-w-md mx-auto px-4 py-12 sm:py-16">
    <div class="bg-white rounded-3xl border border-[#E2ECE5] p-6 sm:p-8 shadow-md space-y-6">
        <div class="text-center space-y-1">
            <h1 class="text-2xl font-black text-[#051F20]">Create an account</h1>
            <p class="text-xs text-slate-500">Keep track of your orders and save your delivery address.</p>
        </div>

        @if($errors->any())
        <div role="alert" class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs">
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form action="{{ route('register') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <div>
                <label for="name" class="block font-bold text-[#051F20] mb-1">Full name</label>
                <input id="name" type="text" name="name" required autocomplete="name" maxlength="100" value="{{ old('name') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-[#E2ECE5] focus:border-[#235347] focus:outline-none" />
            </div>

            <div>
                <label for="email" class="block font-bold text-[#051F20] mb-1">Email address</label>
                <input id="email" type="email" name="email" required autocomplete="email" maxlength="255" value="{{ old('email') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-[#E2ECE5] focus:border-[#235347] focus:outline-none" />
            </div>

            <div>
                <label for="phone" class="block font-bold text-[#051F20] mb-1">Phone or Telegram number (optional)</label>
                <input id="phone" type="tel" name="phone" autocomplete="tel" maxlength="30" pattern="[0-9+\s\-\(\)]+" value="{{ old('phone') }}" class="w-full px-3.5 py-2.5 rounded-xl border border-[#E2ECE5] focus:border-[#235347] focus:outline-none" />
            </div>

            <div>
                <label for="password" class="block font-bold text-[#051F20] mb-1">Password</label>
                <input id="password" type="password" name="password" required autocomplete="new-password" minlength="{{ $minLength }}" maxlength="255" aria-describedby="password-help" class="w-full px-3.5 py-2.5 rounded-xl border border-[#E2ECE5] focus:border-[#235347] focus:outline-none" />
                <p id="password-help" class="mt-1 text-[11px] text-slate-500">At least {{ $minLength }} characters, including a letter and a number.</p>
            </div>

            <div>
                <label for="password_confirmation" class="block font-bold text-[#051F20] mb-1">Confirm password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" maxlength="255" class="w-full px-3.5 py-2.5 rounded-xl border border-[#E2ECE5] focus:border-[#235347] focus:outline-none" />
            </div>

            <p class="text-[11px] text-slate-500">
                By creating an account you agree to our <a href="{{ route('terms') }}" class="underline">Terms and Conditions</a>
                and acknowledge our <a href="{{ route('privacy') }}" class="underline">Privacy Policy</a>.
            </p>

            <button type="submit" class="btn-press w-full py-3 rounded-xl bg-[#051F20] hover:bg-[#0B2B26] text-white font-extrabold text-xs shadow-md transition-all">
                Create account
            </button>
        </form>

        <div class="text-center text-xs text-slate-500 pt-2 border-t border-[#E2ECE5]">
            Already have an account? <a href="{{ route('login') }}" class="font-bold text-[#235347] hover:underline">Sign in</a>
        </div>
    </div>
</div>
@endsection
