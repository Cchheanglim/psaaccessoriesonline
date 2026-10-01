@extends('layouts.app')

@section('content')
<article class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
    <header class="mb-8 border-b border-[#EFE4D6] pb-6">
        <h1 class="text-2xl sm:text-3xl font-black text-[#2B1D1D] tracking-tight">@yield('heading')</h1>
        <p class="mt-2 text-xs text-stone-500">Last updated: @yield('updated')</p>
    </header>

    <div class="space-y-6 text-sm leading-relaxed text-[#4A3333]
                [&_h2]:text-base [&_h2]:font-black [&_h2]:text-[#2B1D1D] [&_h2]:mt-8 [&_h2]:mb-2
                [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-1
                [&_a]:text-[#E88C35] [&_a]:underline">
        @yield('body')
    </div>
</article>
@endsection
