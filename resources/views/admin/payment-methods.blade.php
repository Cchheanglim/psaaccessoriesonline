@extends('layouts.admin')

@section('title', 'Payment Methods | PsaOnlineAccessories Admin')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-[#2B1D1D]">Payment methods</h1>
        <p class="text-xs text-stone-500">Bank details shown to customers. Exchange rate: 1 USD = 4,100 KHR.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @forelse ($methods as $method)
        <div class="bg-[#0B2B26] p-6 rounded-2xl border border-[#235347] space-y-4">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-base font-extrabold text-white">{{ $method->name }}</h2>
                <span class="px-2 py-0.5 rounded-md font-bold text-[10px] {{ $method->is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-stone-500/20 text-stone-300' }}">
                    {{ $method->is_active ? 'Active' : 'Off' }}
                </span>
            </div>
            @if ($method->description)
                <p class="text-xs text-slate-300">{{ $method->description }}</p>
            @endif

            <dl class="space-y-2 text-xs">
                <div><dt class="inline text-[#8EB69B]">Code:</dt> <dd class="inline font-mono text-white">{{ $method->code }}</dd></div>
                @if ($method->account_name)
                <div><dt class="inline text-[#8EB69B]">Account name:</dt> <dd class="inline text-white">{{ $method->account_name }}</dd></div>
                @endif
                @if ($method->account_number)
                <div><dt class="inline text-[#8EB69B]">Account:</dt> <dd class="inline font-mono text-white">{{ $method->account_number }}</dd></div>
                @endif
            </dl>

            <form action="{{ route('admin.payment-methods.toggle', $method->id) }}" method="POST">
                @csrf
                <button type="submit" class="px-3 py-1.5 rounded-lg border border-[#235347] text-xs font-bold {{ $method->is_active ? 'text-red-300 hover:bg-red-500/10' : 'text-emerald-300 hover:bg-emerald-500/10' }}">
                    {{ $method->is_active ? 'Turn off' : 'Turn on' }}
                </button>
            </form>
        </div>
        @empty
        <p class="text-xs text-stone-500">No payment methods configured. Run the database seeder to add the defaults.</p>
        @endforelse
    </div>
</div>
@endsection
